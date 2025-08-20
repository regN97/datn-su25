<?php

namespace App\Http\Controllers\Cashier;

use Carbon\Carbon;
use App\Models\Bill;
use App\Models\Product;
use App\Models\Customer;
use App\Models\BatchItem;
use App\Models\Promotion;
use App\Mail\ReceiptEmail;
use App\Models\BillDetail;
use Illuminate\Http\Request;
use App\Services\VNPayService;
use Illuminate\Support\Facades\DB;
use App\Models\CashRegisterSession;
use Illuminate\Support\Facades\Log;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class VNPayController
{
    private $vnpayService;
    private const PAYMENT_STATUS_PENDING = 1;
    private const PAYMENT_STATUS_SUCCESS = 2;
    private const PAYMENT_STATUS_FAILED = 3;

    public function __construct(VNPayService $vnpayService)
    {
        $this->vnpayService = $vnpayService;
    }

    /**
     * Retrieve products with available stock
     */
    private function getProducts()
    {
        $products = Product::with('category')
            ->select('id', 'name', 'category_id', 'selling_price', 'image_url', 'sku', 'barcode', 'stock_quantity')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get();

        $productIds = $products->pluck('id')->toArray();
        $stocks = $this->calculateAvailableStock($productIds);

        return $products->map(function ($product) use ($stocks) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category ? $product->category->name : 'Chưa phân loại',
                'price' => $product->selling_price ?? 0,
                'stock' => $stocks[$product->id] ?? 0,
                'image' => $product->image_url ?? '/storage/piclumen-1747750187180.png',
                'sku' => $product->sku,
                'barcode' => $product->barcode,
            ];
        })->toArray();
    }

    /**
     * Calculate available stock for products
     */
    private function calculateAvailableStock($productIds, $singleProduct = false)
    {
        $productIds = is_array($productIds) ? $productIds : [$productIds];

        $batchQuantities = BatchItem::whereIn('product_id', $productIds)
            ->whereIn('inventory_status', ['active', 'low_stock'])
            ->where('current_quantity', '>', 0)
            ->whereHas('batch', function ($query) {
                $query->whereNull('deleted_at')
                    ->whereIn('receipt_status', ['completed', 'partially_received']);
            })
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', Carbon::today('Asia/Ho_Chi_Minh'));
            })
            ->groupBy('product_id')
            ->select('product_id', DB::raw('SUM(current_quantity) as total_quantity'))
            ->pluck('total_quantity', 'product_id')
            ->toArray();

        $stocks = [];
        foreach ($productIds as $productId) {
            $stocks[$productId] = $batchQuantities[$productId] ?? 0;
        }

        $products = Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->select('id', 'stock_quantity')
            ->get();

        foreach ($products as $product) {
            $batchQuantity = $stocks[$product->id] ?? 0;
            if ($product->stock_quantity != $batchQuantity) {
                $product->stock_quantity = $batchQuantity;
                $product->save();
            }
        }

        return $singleProduct ? ($stocks[$productIds[0]] ?? 0) : $stocks;
    }

    /**
     * Create VNPay payment URL
     */
    public function createVNPayPayment(Request $request)
    {
        try {
            $user = Auth::user();
            if ($user->role_id !== 3) {
                return response()->json(['errors' => ['server' => 'Không có quyền truy cập.']], 403);
            }

            // Cập nhật xác thực để bao gồm walletAmount
            $validator = Validator::make($request->all(), [
                'cart' => 'required|array|min:1',
                'cart.*.id' => 'required|integer|exists:products,id,deleted_at,NULL,is_active,1',
                'cart.*.quantity' => 'required|integer|min:1',
                'customer_id' => 'nullable|integer|exists:customers,id,deleted_at,NULL',
                'amount' => 'required|numeric|min:1000',
                'orderNotes' => 'nullable|string|max:255',
                'bank_code' => 'nullable|string|max:50',
                'couponCode' => 'nullable|string|exists:promotions,coupon_code',
                'walletAmount' => 'nullable|numeric|min:0', // Thêm xác thực walletAmount
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();

            // Kiểm tra số dư ví nếu có customer_id và walletAmount
            if ($data['customer_id'] && isset($data['walletAmount']) && $data['walletAmount'] > 0) {
                $customer = Customer::lockForUpdate()->find($data['customer_id']);
                if (!$customer) {
                    return response()->json(['errors' => ['customer_id' => 'Khách hàng không tồn tại.']], 422);
                }
                if ($customer->wallet < $data['walletAmount']) {
                    return response()->json(['errors' => ['walletAmount' => 'Số tiền từ ví vượt quá số dư.']], 422);
                }
            }

            $session = CashRegisterSession::where('user_id', $user->id)
                ->whereNull('closed_at')
                ->whereNull('deleted_at')
                ->first();

            if (!$session) {
                return response()->json(['errors' => ['server' => 'Không tìm thấy phiên thu ngân đang hoạt động.']], 422);
            }

            return DB::transaction(function () use ($data, $user, $session, $request) {
                $subTotal = 0;
                $discountAmount = 0;
                $promotion = null;
                $freeItems = [];
                $updatedProducts = [];

                // Xử lý khuyến mãi
                if (!empty($data['couponCode'])) {
                    $promotion = Promotion::where('coupon_code', $data['couponCode'])
                        ->where('is_active', true)
                        ->where('start_date', '<=', Carbon::now('Asia/Ho_Chi_Minh'))
                        ->where('end_date', '>=', Carbon::now('Asia/Ho_Chi_Minh'))
                        ->where(function ($query) {
                            $query->whereNull('usage_limit')
                                ->orWhereRaw('usage_count < usage_limit');
                        })
                        ->first();

                    if (!$promotion) {
                        return response()->json(['errors' => ['couponCode' => 'Mã khuyến mãi không hợp lệ hoặc đã hết hạn.']], 422);
                    }
                }

                // Kiểm tra tồn kho
                $productIds = array_column($data['cart'], 'id');
                $stocks = $this->calculateAvailableStock($productIds);

                foreach ($data['cart'] as $item) {
                    $product = Product::find($item['id']);
                    if (!$product) {
                        return response()->json(['errors' => ['cart' => "Sản phẩm ID {$item['id']} không tồn tại."]], 422);
                    }

                    $availableStock = $stocks[$item['id']] ?? 0;
                    if ($availableStock < $item['quantity']) {
                        return response()->json(['errors' => ['cart' => "Sản phẩm {$product->name} không đủ tồn kho. Chỉ còn {$availableStock}."]], 422);
                    }

                    $subTotal += $item['quantity'] * $product->selling_price;
                }

                // Áp dụng khuyến mãi
                if ($promotion) {
                    if ($promotion->type_id == 1) {
                        $discountAmount = $promotion->discount_value;
                    } elseif ($promotion->type_id == 2) {
                        $discountAmount = ($promotion->discount_value / 100) * $subTotal;
                    } elseif ($promotion->type_id == 4 && $subTotal >= $promotion->min_order_value) {
                        $discountAmount = $promotion->discount_value;
                    } elseif ($promotion->type_id == 3) {
                        $buyQuantity = $promotion->buy_quantity;
                        $getQuantity = $promotion->get_quantity;
                        $promotionProducts = $promotion->products()->pluck('product_id')->toArray();

                        foreach ($data['cart'] as $item) {
                            if (in_array($item['id'], $promotionProducts)) {
                                $eligibleSets = floor($item['quantity'] / $buyQuantity);
                                $freeQuantity = $eligibleSets * $getQuantity;

                                if ($freeQuantity > 0) {
                                    $freeItems[] = [
                                        'product_id' => $item['id'],
                                        'quantity' => $freeQuantity,
                                        'unit_price' => 0,
                                    ];
                                    $discountAmount += $freeQuantity * Product::find($item['id'])->selling_price;
                                }
                            }
                        }
                    }
                }

                // Tính tổng cần thanh toán (bao gồm trừ ví)
                $walletAmount = $data['walletAmount'] ?? 0;
                $totalAmount = $subTotal - $discountAmount - $walletAmount;
                if ($totalAmount != $data['amount']) {
                    return response()->json(['errors' => ['amount' => 'Số tiền không khớp với giỏ hàng sau khi trừ ví và khuyến mãi.']], 422);
                }

                // Trừ tạm thời số dư ví
                if ($data['customer_id'] && $walletAmount > 0) {
                    $customer = Customer::lockForUpdate()->find($data['customer_id']);
                    $customer->wallet -= $walletAmount;
                    $customer->save();
                }

                // Tạo hóa đơn
                $billNumber = 'BILL-' . Carbon::now('Asia/Ho_Chi_Minh')->format('YmdHis') . '-' . rand(1000, 9999);
                $txnRef = substr(preg_replace('/[^A-Za-z0-9]/', '', $billNumber), 0, 20);

                $bill = Bill::create([
                    'bill_number' => $billNumber,
                    'customer_id' => $data['customer_id'] ?? null,
                    'sub_total' => $subTotal,
                    'discount_amount' => $discountAmount,
                    'wallet_amount' => $walletAmount, // Lưu walletAmount vào hóa đơn
                    'total_amount' => $totalAmount,
                    'received_money' => 0,
                    'change_money' => 0,
                    'payment_method' => 'vnpay',
                    'payment_status_id' => self::PAYMENT_STATUS_PENDING,
                    'notes' => $data['orderNotes'] ?? null,
                    'cashier_id' => $user->id,
                    'session_id' => $session->id,
                    'txn_ref' => $txnRef,
                    'created_at' => now('Asia/Ho_Chi_Minh'),
                    'updated_at' => now('Asia/Ho_Chi_Minh'),
                ]);

                // Xử lý các mặt hàng trong giỏ
                foreach ($data['cart'] as $item) {
                    $product = Product::lockForUpdate()->find($item['id']);
                    $remainingQuantity = $item['quantity'];

                    $batchItems = BatchItem::where('product_id', $item['id'])
                        ->whereIn('inventory_status', ['active', 'low_stock'])
                        ->where('current_quantity', '>', 0)
                        ->whereHas('batch', function ($query) {
                            $query->whereNull('deleted_at')
                                ->whereIn('receipt_status', ['completed', 'partially_received']);
                        })
                        ->where(function ($query) {
                            $query->whereNull('expiry_date')
                                ->orWhere('expiry_date', '>=', Carbon::today('Asia/Ho_Chi_Minh'));
                        })
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate()
                        ->get();

                    if ($batchItems->isEmpty()) {
                        throw new \Exception("Không tìm thấy lô hàng hợp lệ cho sản phẩm {$product->name}.");
                    }

                    foreach ($batchItems as $batchItem) {
                        if ($remainingQuantity <= 0) {
                            break;
                        }

                        $quantityToDeduct = min($batchItem->current_quantity, $remainingQuantity);
                        if ($quantityToDeduct > 0) {
                            $newQuantity = $batchItem->current_quantity - $quantityToDeduct;
                            $inventoryStatus = $newQuantity <= 0 ? 'out_of_stock' : ($newQuantity <= ($batchItem->min_stock_level ?? 0) ? 'low_stock' : 'active');

                            $batchItem->update([
                                'current_quantity' => $newQuantity,
                                'inventory_status' => $inventoryStatus,
                                'updated_at' => now('Asia/Ho_Chi_Minh'),
                            ]);

                            InventoryTransaction::create([
                                'transaction_type_id' => 2,
                                'product_id' => $product->id,
                                'quantity_change' => -$quantityToDeduct,
                                'stock_after' => $product->stock_quantity - $quantityToDeduct,
                                'unit_price' => $product->selling_price,
                                'total_value' => $quantityToDeduct * $product->selling_price,
                                'transaction_date' => now('Asia/Ho_Chi_Minh'),
                                'related_bill_id' => $bill->id,
                                'related_batch_id' => $batchItem->batch_id,
                                'user_id' => $user->id,
                                'created_at' => now('Asia/Ho_Chi_Minh'),
                                'updated_at' => now('Asia/Ho_Chi_Minh'),
                            ]);

                            BillDetail::create([
                                'bill_id' => $bill->id,
                                'product_id' => $product->id,
                                'batch_id' => $batchItem->batch_id,
                                'p_name' => $product->name,
                                'p_sku' => $product->sku,
                                'p_barcode' => $product->barcode,
                                'quantity' => $quantityToDeduct,
                                'unit_cost' => $batchItem->purchase_price,
                                'unit_price' => $product->selling_price,
                                'discount_per_item' => 0,
                                'subtotal' => $quantityToDeduct * $product->selling_price,
                                'created_at' => now('Asia/Ho_Chi_Minh'),
                                'updated_at' => now('Asia/Ho_Chi_Minh'),
                            ]);

                            $remainingQuantity -= $quantityToDeduct;
                        }
                    }

                    if ($remainingQuantity > 0) {
                        throw new \Exception("Không đủ hàng trong kho cho sản phẩm {$product->name}.");
                    }

                    $product->stock_quantity -= $item['quantity'];
                    $product->last_sold_at = now('Asia/Ho_Chi_Minh');
                    $product->save();

                    $updatedProducts[] = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'category' => $product->category ? $product->category->name : 'Chưa phân loại',
                        'price' => $product->selling_price ?? 0,
                        'stock' => $product->stock_quantity ?? 0,
                        'image' => $product->image_url ?? '/storage/piclumen-1747750187180.png',
                        'sku' => $product->sku,
                        'barcode' => $product->barcode,
                    ];
                }

                // Xử lý mặt hàng miễn phí từ khuyến mãi
                foreach ($freeItems as $freeItem) {
                    $product = Product::lockForUpdate()->find($freeItem['product_id']);
                    $remainingQuantity = $freeItem['quantity'];

                    $batchItems = BatchItem::where('product_id', $freeItem['product_id'])
                        ->whereIn('inventory_status', ['active', 'low_stock'])
                        ->where('current_quantity', '>', 0)
                        ->whereHas('batch', function ($query) {
                            $query->whereNull('deleted_at')
                                ->whereIn('receipt_status', ['completed', 'partially_received']);
                        })
                        ->where(function ($query) {
                            $query->whereNull('expiry_date')
                                ->orWhere('expiry_date', '>=', Carbon::today('Asia/Ho_Chi_Minh'));
                        })
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate()
                        ->get();

                    if ($batchItems->isEmpty()) {
                        throw new \Exception("Không tìm thấy lô hàng hợp lệ cho sản phẩm miễn phí {$product->name}.");
                    }

                    foreach ($batchItems as $batchItem) {
                        if ($remainingQuantity <= 0) {
                            break;
                        }

                        $quantityToDeduct = min($batchItem->current_quantity, $remainingQuantity);
                        if ($quantityToDeduct > 0) {
                            $newQuantity = $batchItem->current_quantity - $quantityToDeduct;
                            $inventoryStatus = $newQuantity <= 0 ? 'out_of_stock' : ($newQuantity <= ($batchItem->min_stock_level ?? 0) ? 'low_stock' : 'active');

                            $batchItem->update([
                                'current_quantity' => $newQuantity,
                                'inventory_status' => $inventoryStatus,
                                'updated_at' => now('Asia/Ho_Chi_Minh'),
                            ]);

                            InventoryTransaction::create([
                                'transaction_type_id' => 2,
                                'product_id' => $product->id,
                                'quantity_change' => -$quantityToDeduct,
                                'stock_after' => $product->stock_quantity - $quantityToDeduct,
                                'unit_price' => 0,
                                'total_value' => 0,
                                'transaction_date' => now('Asia/Ho_Chi_Minh'),
                                'related_bill_id' => $bill->id,
                                'related_batch_id' => $batchItem->batch_id,
                                'user_id' => $user->id,
                                'created_at' => now('Asia/Ho_Chi_Minh'),
                                'updated_at' => now('Asia/Ho_Chi_Minh'),
                            ]);

                            BillDetail::create([
                                'bill_id' => $bill->id,
                                'product_id' => $product->id,
                                'batch_id' => $batchItem->batch_id,
                                'p_name' => $product->name,
                                'p_sku' => $product->sku,
                                'p_barcode' => $product->barcode,
                                'quantity' => $quantityToDeduct,
                                'unit_cost' => $batchItem->purchase_price,
                                'unit_price' => 0,
                                'discount_per_item' => $product->selling_price,
                                'subtotal' => 0,
                                'created_at' => now('Asia/Ho_Chi_Minh'),
                                'updated_at' => now('Asia/Ho_Chi_Minh'),
                            ]);

                            $remainingQuantity -= $quantityToDeduct;
                        }
                    }

                    $product->stock_quantity -= $freeItem['quantity'];
                    $product->last_sold_at = now('Asia/Ho_Chi_Minh');
                    $product->save();

                    $updatedProducts[] = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'category' => $product->category ? $product->category->name : 'Chưa phân loại',
                        'price' => $product->selling_price ?? 0,
                        'stock' => $product->stock_quantity ?? 0,
                        'image' => $product->image_url ?? '/storage/piclumen-1747750187180.png',
                        'sku' => $product->sku,
                        'barcode' => $product->barcode,
                    ];
                }

                // Tạo URL thanh toán VNPay
                $vnpayUrl = $this->vnpayService->createPaymentUrl(
                    $txnRef,
                    $bill->total_amount, // Sử dụng total_amount từ bill
                    'Thanh toán hóa đơn ' . $bill->bill_number,
                    $request->ip(),
                    $data['bank_code'] ?? null
                );

                if ($promotion) {
                    $promotion->increment('usage_count');
                }

                Log::info('Tạo URL thanh toán VNPay thành công', [
                    'bill_id' => $bill->id,
                    'txn_ref' => $txnRef,
                    'amount' => $bill->total_amount,
                    'wallet_amount' => $walletAmount,
                    'vnpay_url' => $vnpayUrl,
                    'user_id' => $user->id,
                    'session_id' => $session->id,
                ]);

                return response()->json([
                    'vnpayUrl' => $vnpayUrl,
                    'bill' => [
                        'id' => $bill->id,
                        'bill_number' => $bill->bill_number,
                        'total_amount' => $bill->total_amount,
                        'wallet_amount' => $bill->wallet_amount,
                        'created_at' => $bill->created_at->setTimezone('Asia/Ho_Chi_Minh')->toISOString(),
                    ],
                    'products' => $updatedProducts,
                ], 200);
            });
        } catch (\Exception $e) {
            // Hoàn lại số dư ví nếu có lỗi
            if (isset($data['customer_id']) && isset($data['walletAmount']) && $data['walletAmount'] > 0) {
                $customer = Customer::lockForUpdate()->find($data['customer_id']);
                if ($customer) {
                    $customer->wallet += $data['walletAmount'];
                    $customer->save();
                }
            }

            // Hoàn lại tồn kho
            foreach ($data['cart'] as $item) {
                $batchItems = BatchItem::where('product_id', $item['id'])
                    ->whereIn('inventory_status', ['active', 'low_stock'])
                    ->where('current_quantity', '>=', 0)
                    ->whereHas('batch', function ($query) {
                        $query->whereNull('deleted_at')
                            ->whereIn('receipt_status', ['completed', 'partially_received']);
                    })
                    ->where(function ($query) {
                        $query->whereNull('expiry_date')
                            ->orWhere('expiry_date', '>=', Carbon::today('Asia/Ho_Chi_Minh'));
                    })
                    ->get();

                foreach ($batchItems as $batch) {
                    $batch->current_quantity += $item['quantity'];
                    $batch->save();

                    Log::info('Rollback inventory', [
                        'batch_id' => $batch->id,
                        'product_id' => $item['id'],
                        'quantity_restored' => $item['quantity'],
                    ]);

                    InventoryTransaction::create([
                        'transaction_type_id' => 3,
                        'product_id' => $item['id'],
                        'quantity_change' => $item['quantity'],
                        'stock_after' => $batch->current_quantity,
                        'unit_price' => $batch->purchase_price,
                        'total_value' => $item['quantity'] * $batch->purchase_price,
                        'transaction_date' => now('Asia/Ho_Chi_Minh'),
                        'related_bill_id' => $bill->id ?? null,
                        'user_id' => $user->id,
                        'note' => 'Hoàn lại tồn kho do lỗi tạo hóa đơn VNPay',
                    ]);
                }
            }

            Log::error('Lỗi khi tạo thanh toán VNPay', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? null,
            ]);
            return response()->json(['errors' => ['server' => 'Lỗi khi tạo thanh toán VNPay: ' . $e->getMessage()]], 500);
        }
    }

    /**
     * Handle VNPay callback
     */
    public function handleVNPayCallback(Request $request)
    {
        try {
            $inputData = $request->query();

            if (!$this->vnpayService->verifyCallback($inputData)) {
                Log::warning('Chữ ký VNPay không hợp lệ trong callback', [
                    'txn_ref' => $inputData['vnp_TxnRef'] ?? '',
                ]);
                return redirect()->away(config('vnpay.frontend_failed_url') . '?status=failed&message=' . urlencode('Chữ ký VNPay không hợp lệ'));
            }

            $txnRef = $inputData['vnp_TxnRef'] ?? null;
            $bill = Bill::where('txn_ref', $txnRef)->first();

            if (!$bill) {
                Log::warning('Không tìm thấy hóa đơn trong callback VNPay', ['txn_ref' => $txnRef]);
                return redirect()->away(config('vnpay.frontend_failed_url') . '?status=failed&message=' . urlencode('Không tìm thấy hóa đơn'));
            }

            if ($bill->payment_status_id !== self::PAYMENT_STATUS_PENDING) {
                Log::warning('Hóa đơn đã được xử lý trước đó', [
                    'bill_id' => $bill->id,
                    'txn_ref' => $txnRef,
                    'payment_status_id' => $bill->payment_status_id,
                ]);
                return redirect()->away(config('vnpay.frontend_failed_url') . '?status=failed&message=' . urlencode('Hóa đơn đã được xử lý'));
            }

            if ($inputData['vnp_Amount'] / 100 !== $bill->total_amount) {
                Log::warning('Số tiền VNPay không khớp với hóa đơn', [
                    'bill_id' => $bill->id,
                    'txn_ref' => $txnRef,
                    'vnp_amount' => $inputData['vnp_Amount'] / 100,
                    'bill_amount' => $bill->total_amount,
                ]);
                return redirect()->away(config('vnpay.frontend_failed_url') . '?status=failed&message=' . urlencode('Số tiền không khớp'));
            }

            return DB::transaction(function () use ($inputData, $bill, $txnRef) {
                if ($inputData['vnp_ResponseCode'] === '00') {
                    $bill->update([
                        'payment_status_id' => self::PAYMENT_STATUS_SUCCESS,
                        'received_money' => $bill->total_amount,
                        'change_money' => 0,
                        'updated_at' => now('Asia/Ho_Chi_Minh'),
                    ]);

                    // Update customer wallet
                    if ($bill->customer_id) {
                        $customer = Customer::lockForUpdate()->find($bill->customer_id);
                        if ($customer) {
                            $walletBonus = $bill->total_amount * 0.001;
                            $customer->wallet += $walletBonus;
                            $customer->save();

                            // Send receipt email
                            if ($customer->email) {
                                try {
                                    Mail::to($customer->email)->queue(new ReceiptEmail($bill));
                                } catch (\Exception $e) {
                                    Log::warning('Không thể gửi email hóa đơn', [
                                        'bill_id' => $bill->id,
                                        'customer_id' => $customer->id,
                                        'error' => $e->getMessage(),
                                    ]);
                                }
                            }
                        }
                    }

                    // Update session actual amount
                    $session = CashRegisterSession::find($bill->session_id);
                    if ($session) {
                        $session->actual_amount = ($session->actual_amount ?? 0) + $bill->total_amount;
                        $session->save();
                    }

                    Log::info('Thanh toán VNPay thành công qua callback', [
                        'bill_id' => $bill->id,
                        'txn_ref' => $txnRef,
                    ]);
                    return redirect()->away(config('vnpay.frontend_success_url') . '?status=success&bill_id=' . $bill->id . '&txn_ref=' . $txnRef);
                }

                $bill->update([
                    'payment_status_id' => self::PAYMENT_STATUS_FAILED,
                    'deleted_at' => now('Asia/Ho_Chi_Minh'),
                    'updated_at' => now('Asia/Ho_Chi_Minh'),
                ]);

                // Rollback inventory
                $billDetails = BillDetail::where('bill_id', $bill->id)->get();
                foreach ($billDetails as $detail) {
                    $batch = BatchItem::where('id', $detail->batch_id)->first();
                    if ($batch) {
                        $batch->current_quantity += $detail->quantity;
                        $batch->save();

                        InventoryTransaction::create([
                            'transaction_type_id' => 3,
                            'product_id' => $detail->product_id,
                            'quantity_change' => $detail->quantity,
                            'stock_after' => $batch->current_quantity,
                            'unit_price' => $batch->purchase_price,
                            'total_value' => $detail->quantity * $batch->purchase_price,
                            'transaction_date' => now('Asia/Ho_Chi_Minh'),
                            'related_bill_id' => $bill->id,
                            'user_id' => Auth::id() ?? 1,
                            'note' => 'Hoàn lại tồn kho do thanh toán VNPay thất bại',
                        ]);
                    }
                }

                Log::warning('Thanh toán VNPay thất bại qua callback', [
                    'bill_id' => $bill->id,
                    'txn_ref' => $txnRef,
                    'response_code' => $inputData['vnp_ResponseCode'],
                ]);
                return redirect()->away(config('vnpay.frontend_failed_url') . '?status=failed&message=' . urlencode('Thanh toán thất bại') . '&response_code=' . $inputData['vnp_ResponseCode']);
            });
        } catch (\Exception $e) {
            Log::error('Lỗi khi xử lý callback VNPay', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->away(config('vnpay.frontend_failed_url') . '?status=failed&message=' . urlencode('Lỗi khi xử lý callback: ' . $e->getMessage()));
        }
    }

    /**
     * Handle VNPay IPN
     */
    public function handleVNPayIPN(Request $request)
    {
        try {
            $inputData = $request->all();
            if (!$this->vnpayService->verifyCallback($inputData)) {
                Log::warning('Chữ ký VNPay không hợp lệ trong IPN', [
                    'txn_ref' => $inputData['vnp_TxnRef'] ?? '',
                ]);
                return response()->json([
                    'RspCode' => '97',
                    'Message' => 'Chữ ký không hợp lệ',
                ], 400);
            }

            $txnRef = $inputData['vnp_TxnRef'] ?? null;
            $bill = Bill::where('txn_ref', $txnRef)->first();

            if (!$bill) {
                Log::warning('Không tìm thấy hóa đơn trong IPN VNPay', ['txn_ref' => $txnRef]);
                return response()->json([
                    'RspCode' => '01',
                    'Message' => 'Không tìm thấy hóa đơn',
                ], 404);
            }

            if ($bill->payment_status_id !== self::PAYMENT_STATUS_PENDING) {
                Log::warning('Hóa đơn đã được xử lý trước đó trong IPN', [
                    'bill_id' => $bill->id,
                    'txn_ref' => $txnRef,
                    'payment_status_id' => $bill->payment_status_id,
                ]);
                return response()->json([
                    'RspCode' => '02',
                    'Message' => 'Hóa đơn đã được xử lý',
                ], 400);
            }

            if ($inputData['vnp_Amount'] / 100 !== $bill->total_amount) {
                Log::warning('Số tiền VNPay không khớp với hóa đơn', [
                    'bill_id' => $bill->id,
                    'txn_ref' => $txnRef,
                    'vnp_amount' => $inputData['vnp_Amount'] / 100,
                    'bill_amount' => $bill->total_amount,
                ]);
                return response()->json([
                    'RspCode' => '99',
                    'Message' => 'Số tiền không khớp',
                ], 400);
            }

            return DB::transaction(function () use ($inputData, $bill, $txnRef) {
                if ($inputData['vnp_ResponseCode'] === '00') {
                    $bill->update([
                        'payment_status_id' => self::PAYMENT_STATUS_SUCCESS,
                        'received_money' => $bill->total_amount,
                        'change_money' => 0,
                        'updated_at' => now('Asia/Ho_Chi_Minh'),
                    ]);

                    // Update customer wallet
                    if ($bill->customer_id) {
                        $customer = Customer::lockForUpdate()->find($bill->customer_id);
                        if ($customer) {
                            $walletBonus = $bill->total_amount * 0.001;
                            $customer->wallet += $walletBonus;
                            $customer->save();

                            // Send receipt email
                            if ($customer->email) {
                                try {
                                    Mail::to($customer->email)->queue(new ReceiptEmail($bill));
                                } catch (\Exception $e) {
                                    Log::warning('Không thể gửi email hóa đơn', [
                                        'bill_id' => $bill->id,
                                        'customer_id' => $customer->id,
                                        'error' => $e->getMessage(),
                                    ]);
                                }
                            }
                        }
                    }

                    // Update session actual amount
                    $session = CashRegisterSession::find($bill->session_id);
                    if ($session) {
                        $session->actual_amount = ($session->actual_amount ?? 0) + $bill->total_amount;
                        $session->save();
                    }

                    Log::info('Thanh toán VNPay được xác nhận qua IPN', [
                        'bill_id' => $bill->id,
                        'txn_ref' => $txnRef,
                    ]);
                    return response()->json([
                        'RspCode' => '00',
                        'Message' => 'Xác nhận thành công',
                    ], 200);
                }

                $bill->update([
                    'payment_status_id' => self::PAYMENT_STATUS_FAILED,
                    'deleted_at' => now('Asia/Ho_Chi_Minh'),
                    'updated_at' => now('Asia/Ho_Chi_Minh'),
                ]);

                // Rollback inventory
                $billDetails = BillDetail::where('bill_id', $bill->id)->get();
                foreach ($billDetails as $detail) {
                    $batch = BatchItem::where('id', $detail->batch_id)->first();
                    if ($batch) {
                        $batch->current_quantity += $detail->quantity;
                        $batch->save();

                        InventoryTransaction::create([
                            'transaction_type_id' => 3,
                            'product_id' => $detail->product_id,
                            'quantity_change' => $detail->quantity,
                            'stock_after' => $batch->current_quantity,
                            'unit_price' => $batch->purchase_price,
                            'total_value' => $detail->quantity * $batch->purchase_price,
                            'transaction_date' => now('Asia/Ho_Chi_Minh'),
                            'related_bill_id' => $bill->id,
                            'user_id' => Auth::id() ?? 1,
                            'note' => 'Hoàn lại tồn kho do thanh toán VNPay thất bại',
                        ]);
                    }
                }

                Log::warning('Thanh toán VNPay thất bại qua IPN', [
                    'bill_id' => $bill->id,
                    'txn_ref' => $txnRef,
                    'response_code' => $inputData['vnp_ResponseCode'],
                ]);
                return response()->json([
                    'RspCode' => '00',
                    'Message' => 'Xác nhận thành công (thanh toán thất bại)',
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Lỗi khi xử lý IPN VNPay', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'RspCode' => '99',
                'Message' => 'Lỗi không xác định',
            ], 500);
        }
    }

    /**
     * Get products for public access
     */
    public function getProductsPublic(Request $request)
    {
        try {
            $products = $this->getProducts();

            return response()->json([
                'products' => $products,
                'success' => 'Danh sách sản phẩm đã được tải thành công.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => 'Lỗi tải danh sách sản phẩm: ' . $e->getMessage()]], 500);
        }
    }
}