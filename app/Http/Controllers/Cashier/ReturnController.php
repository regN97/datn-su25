<?php

namespace App\Http\Controllers\Cashier;

use Carbon\Carbon;
use App\Models\Bill;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Customer;
use App\Models\BatchItem;
use App\Models\ReturnBill;
use Illuminate\Http\Request;
use App\Models\ReturnBillDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReturnController extends Controller
{
    public function index()
    {
        return Inertia::render('cashier/pos/ReturnOrder');
    }

    public function list()
    {
        return Inertia::render('cashier/pos/ReturnList');
    }

    public function getAll(Request $request)
    {
        $query = $request->input('query');
        $date = $request->input('date');

        $returnBills = ReturnBill::with(['bill', 'details.product', 'cashier:id,name', 'customer:id,name'])
            ->when($query, function ($q, $query) {
                $q->where('return_bill_number', 'like', '%' . $query . '%')
                    ->orWhereHas('bill', function ($b) use ($query) {
                        $b->where('bill_number', 'like', '%' . $query . '%');
                    });
            })
            ->when($date, function ($q, $date) {
                $q->whereDate('created_at', $date);
            })
            ->latest()
            ->paginate(10);

        return response()->json($returnBills);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');

        if (!$query) {
            return response()->json(['error' => 'Vui lòng nhập số hóa đơn hoặc số điện thoại.'], 400);
        }

        $bill = Bill::with(['customer', 'details.product', 'details.batch', 'returnBills'])
            ->where('bill_number', $query)
            ->orWhereHas('customer', function ($q) use ($query) {
                $q->where('phone', $query);
            })
            ->first();

        if (!$bill) {
            return response()->json(['error' => 'Không tìm thấy hóa đơn nào.'], 404);
        }

        $isWithin24Hours = $bill->created_at->diffInHours(Carbon::now()) <= 24;
        $hasBeenReturned = $bill->returnBills->isNotEmpty();

        $billData = $bill->toArray();
        $billData['can_be_returned'] = $isWithin24Hours && !$hasBeenReturned;
        $billData['return_status'] = [
            'has_been_returned' => $hasBeenReturned,
            'is_expired' => !$isWithin24Hours,
        ];

        return response()->json($billData);
    }

    public function processReturn(Request $request)
    {
        try {
            $validated = $request->validate([
                'bill_id' => 'required|exists:bills,id',
                'return_items' => 'required|array',
                'return_items.*.product_id' => 'required|exists:products,id',
                'return_items.*.quantity' => 'required|integer|min:1',
                'reason' => 'nullable|string|max:255',
            ]);

            DB::transaction(function () use ($validated) {
                $bill = Bill::with('details')->findOrFail($validated['bill_id']);
                $totalAmountReturned = 0;

                $isWithin24Hours = $bill->created_at->diffInHours(Carbon::now()) <= 24;
                if (!$isWithin24Hours || $bill->returnBills->isNotEmpty()) {
                    throw new \Exception('Hóa đơn không đủ điều kiện để trả hàng.');
                }

                $returnBill = ReturnBill::create([
                    'return_bill_number' => 'RT' . now()->format('YmdHis') . rand(100, 999),
                    'bill_id' => $bill->id,
                    'customer_id' => $bill->customer_id,
                    'cashier_id' => auth()->user()->id,
                    'total_amount_returned' => 0,
                    'reason' => $validated['reason'],
                    'payment_status' => 'unpaid',
                ]);

                foreach ($validated['return_items'] as $item) {
                    $productId = $item['product_id'];
                    $quantityToReturn = $item['quantity'];

                    $billDetailsForProduct = $bill->details->where('product_id', $productId);
                    if ($billDetailsForProduct->isEmpty()) {
                        Log::warning("Sản phẩm có ID {$productId} không tồn tại trong hóa đơn #{$bill->id}.");
                        continue;
                    }

                    $totalQuantityInBill = $billDetailsForProduct->sum('quantity');
                    if ($quantityToReturn > $totalQuantityInBill) {
                        throw new \Exception("Số lượng trả lại của sản phẩm {$billDetailsForProduct->first()->p_name} vượt quá tổng số lượng đã mua.");
                    }

                    $remainingToReturn = $quantityToReturn;
                    foreach ($billDetailsForProduct as $billDetail) {
                        if ($remainingToReturn <= 0) {
                            break;
                        }

                        $quantityInThisDetail = $billDetail->quantity;
                        if ($quantityInThisDetail <= 0) {
                            continue;
                        }

                        $actualReturnQuantity = min($remainingToReturn, $quantityInThisDetail);
                        $subtotal = $actualReturnQuantity * $billDetail->unit_price;
                        $totalAmountReturned += $subtotal;

                        Log::info("Tính subtotal: {$actualReturnQuantity} x {$billDetail->unit_price} = {$subtotal} cho product_id {$billDetail->product_id}");

                        ReturnBillDetail::create([
                            'return_bill_id' => $returnBill->id,
                            'product_id' => $billDetail->product_id,
                            'p_name' => $billDetail->p_name,
                            'returned_quantity' => $actualReturnQuantity,
                            'unit_price' => number_format($billDetail->unit_price, 2, '.', ''),
                            'subtotal' => $subtotal,
                        ]);

                        $product = Product::findOrFail($billDetail->product_id);
                        if (!$product->is_active) {
                            throw new \Exception("Sản phẩm {$billDetail->p_name} đã ngừng kinh doanh.");
                        }
                        $product->increment('stock_quantity', $actualReturnQuantity);

                        $batchItem = BatchItem::where('batch_id', $billDetail->batch_id)
                            ->where('product_id', $billDetail->product_id)
                            ->first();
                        if (!$batchItem) {
                            Log::error("Không tìm thấy batch_item cho product_id {$billDetail->product_id} và batch_id {$billDetail->batch_id}");
                            throw new \Exception("Không tìm thấy lô hàng cho sản phẩm {$billDetail->p_name}.");
                        }
                        if ($batchItem->inventory_status === 'expired' || $batchItem->inventory_status === 'damaged') {
                            throw new \Exception("Lô hàng của sản phẩm {$billDetail->p_name} không hợp lệ để trả hàng.");
                        }
                        $batchItem->increment('current_quantity', $actualReturnQuantity);

                        InventoryTransaction::create([
                            'transaction_type_id' => 4,
                            'product_id' => $billDetail->product_id,
                            'quantity_change' => $actualReturnQuantity,
                            'stock_after' => $product->stock_quantity,
                            'unit_price' => $billDetail->unit_price,
                            'total_value' => $actualReturnQuantity * $billDetail->unit_price,
                            'transaction_date' => now(),
                            'related_bill_id' => $bill->id,
                            'related_purchase_return_id' => null,
                            'related_batch_id' => $billDetail->batch_id,
                            'user_id' => auth()->user()->id,
                            'note' => $validated['reason'] ?? 'Trả hàng từ hóa đơn ' . $bill->bill_number,
                        ]);

                        $remainingToReturn -= $actualReturnQuantity;
                    }
                }

                $returnBill->update([
                    'total_amount_returned' => $totalAmountReturned,
                    'payment_status' => 'paid',
                ]);

                if ($bill->customer_id && $bill->payment_method === 'wallet') {
                    $customer = Customer::findOrFail($bill->customer_id);
                    $customer->increment('wallet', $totalAmountReturned);
                    Log::info("Cập nhật ví khách hàng {$customer->id} với số tiền {$totalAmountReturned}.");
                }
            });

            Log::info("Xử lý trả hàng thành công cho hóa đơn ID {$validated['bill_id']}.");
            return response()->json(['message' => 'Xử lý trả hàng thành công!'], 200);
        } catch (ModelNotFoundException $e) {
            Log::error("Lỗi khi xử lý trả hàng: Hóa đơn hoặc sản phẩm không tồn tại. " . $e->getMessage());
            return response()->json(['error' => 'Hóa đơn hoặc sản phẩm không tồn tại.'], 404);
        } catch (\Exception $e) {
            Log::error("Lỗi khi xử lý trả hàng: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
