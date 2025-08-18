<?php

namespace App\Http\Controllers\Cashier;

use Carbon\Carbon;
use App\Models\Bill;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\Customer;
use App\Models\BatchItem;
use App\Models\Promotion;
use App\Models\UserShift;
use App\Mail\ReceiptEmail;
use App\Models\BillDetail;
use Illuminate\Http\Request;
use App\Models\CashRegisterSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class POSController
{
    private $defaultNotes = [
        'Không có sự cố trong ca',
        'Thiếu tiền mặt khi kiểm kê',
        'Khách hàng phàn nàn về sản phẩm',
        'Hệ thống gặp lỗi tạm thời',
        'Khác (vui lòng ghi rõ)',
    ];

    public function index()
    {
        $user = Auth::user();
        if ($user->role_id !== 3) {
            return redirect()->route('login')->withErrors(['server' => 'Không có quyền truy cập.']);
        }

        $products = Product::with('category')
            ->select('id', 'name', 'category_id', 'selling_price', 'image_url', 'sku', 'barcode', 'stock_quantity')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get();

        $productIds = $products->pluck('id')->toArray();
        $stocks = $this->getAvailableStocks($productIds);

        $products = $products->map(function ($product) use ($stocks) {
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

        $customers = Customer::select('id', 'customer_name', 'email', 'phone', 'address', 'wallet')
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'customer_name' => $customer->customer_name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'address' => $customer->address,
                    'wallet' => $customer->wallet ?? 0,
                ];
            })->toArray();

        $activeSession = CashRegisterSession::select('id', 'user_id', 'user_shift_id', 'opened_at')
            ->where('user_id', $user->id)
            ->whereNull('closed_at')
            ->whereNull('deleted_at')
            ->first();

        $activeShift = null;
        if ($activeSession && $activeSession->user_shift_id) {
            $userShift = UserShift::where('id', $activeSession->user_shift_id)
                ->whereNull('deleted_at')
                ->first();
            if ($userShift) {
                $activeShift = [
                    'id' => $userShift->id,
                    'date' => Carbon::parse($userShift->date, 'Asia/Ho_Chi_Minh')->format('Y-m-d'),
                    'status' => $userShift->status,
                    'check_in' => $userShift->check_in ? Carbon::parse($userShift->check_in, 'Asia/Ho_Chi_Minh')->toISOString() : null,
                    'opened_at' => $activeSession->opened_at ? $activeSession->opened_at->setTimezone('Asia/Ho_Chi_Minh')->toISOString() : null,
                ];
            }
        }

        $currentTime = Carbon::now('Asia/Ho_Chi_Minh');
        $userShifts = UserShift::select('id', 'date', 'status', 'check_in', 'check_out')
            ->where('user_id', $user->id)
            ->where('date', $currentTime->toDateString())
            ->whereIn('status', ['SCHEDULED', 'CHECKED_IN'])
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($shift) use ($currentTime) {
                $isSuitable = $shift->status === 'SCHEDULED' || $shift->status === 'CHECKED_IN';
                return [
                    'id' => $shift->id,
                    'date' => Carbon::parse($shift->date, 'Asia/Ho_Chi_Minh')->format('Y-m-d'),
                    'status' => $shift->status,
                    'check_in' => $shift->check_in ? Carbon::parse($shift->check_in, 'Asia/Ho_Chi_Minh')->format('H:i:s') : null,
                    'check_out' => $shift->check_out ? Carbon::parse($shift->check_out, 'Asia/Ho_Chi_Minh')->format('H:i:s') : null,
                    'is_suitable' => $isSuitable,
                ];
            })->toArray();

        return Inertia::render('cashier/pos/POS', [
            'products' => $products,
            'customers' => $customers,
            'hasActiveSession' => !!$activeSession,
            'activeShift' => $activeShift,
            'userShifts' => $userShifts,
            'defaultNotes' => $this->defaultNotes,
            'flash' => [
                'success' => session('success'),
            ],
            'errors' => session('errors') ? session('errors')->getBag('default') : null,
        ]);
    }

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

    private function getAvailableStock($productId)
    {
        return $this->calculateAvailableStock($productId, true);
    }

    private function getAvailableStocks(array $productIds)
    {
        return $this->calculateAvailableStock($productIds);
    }

    private function checkShiftExpiration($userShift)
    {
        if (!$userShift) {
            return ['isExpired' => true, 'message' => 'Không tìm thấy ca làm việc hoặc ca không hợp lệ.'];
        }

        $currentTime = Carbon::now('Asia/Ho_Chi_Minh');
        $isExpired = $userShift->status === 'CHECKED_OUT' || ($userShift->check_out && $currentTime->greaterThan(Carbon::parse($userShift->check_out, 'Asia/Ho_Chi_Minh')));

        if ($isExpired) {
            return ['isExpired' => true, 'message' => 'Ca làm việc đã hết hạn. Vui lòng đóng ca và mở ca mới.'];
        }

        return ['isExpired' => false, 'message' => ''];
    }

    public function getWorkShifts()
    {
        try {
            $user = Auth::user();
            $currentTime = Carbon::now('Asia/Ho_Chi_Minh');
            $userShifts = UserShift::select('id', 'date', 'status', 'check_in', 'check_out')
                ->where('user_id', $user->id)
                ->where('date', $currentTime->toDateString())
                ->whereIn('status', ['SCHEDULED', 'CHECKED_IN'])
                ->whereNull('deleted_at')
                ->get()
                ->map(function ($shift) use ($currentTime) {
                    $isSuitable = $shift->status === 'SCHEDULED' || $shift->status === 'CHECKED_IN';
                    return [
                        'id' => $shift->id,
                        'date' => Carbon::parse($shift->date, 'Asia/Ho_Chi_Minh')->format('Y-m-d'),
                        'status' => $shift->status,
                        'check_in' => $shift->check_in ? Carbon::parse($shift->check_in, 'Asia/Ho_Chi_Minh')->format('H:i:s') : null,
                        'check_out' => $shift->check_out ? Carbon::parse($shift->check_out, 'Asia/Ho_Chi_Minh')->format('H:i:s') : null,
                        'is_suitable' => $isSuitable,
                    ];
                })->toArray();

            if (empty($userShifts)) {
                return response()->json(['shifts' => [], 'message' => 'Không có ca làm việc được cấu hình.']);
            }

            return response()->json(['shifts' => $userShifts]);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => 'Không thể tải danh sách ca làm việc.']], 500);
        }
    }

    public function startSession(Request $request)
    {
        try {
            $user = Auth::user();
            if ($user->role_id !== 3) {
                return response()->json(['errors' => ['server' => 'Không có quyền truy cập.']], 403);
            }

            $data = $request->validate([
                'opening_amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:255',
            ]);

            $existingShift = UserShift::where('user_id', $user->id)
                ->where('date', Carbon::today('Asia/Ho_Chi_Minh')->toDateString())
                ->whereIn('status', ['SCHEDULED', 'CHECKED_IN'])
                ->whereNull('deleted_at')
                ->first();

            if ($existingShift) {
                return response()->json(['errors' => ['server' => 'Bạn đã có một ca làm việc đang mở trong ngày hôm nay.']], 422);
            }

            $activeSession = CashRegisterSession::where('user_id', $user->id)
                ->whereNull('closed_at')
                ->whereNull('deleted_at')
                ->first();

            if ($activeSession) {
                return response()->json(['errors' => ['server' => 'Bạn đã có một phiên làm việc đang mở.']], 422);
            }

            $userShift = UserShift::create([
                'user_id' => $user->id,
                'date' => Carbon::today('Asia/Ho_Chi_Minh')->toDateString(),
                'status' => 'CHECKED_IN',
                'check_in' => Carbon::now('Asia/Ho_Chi_Minh'),
                'created_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
            ]);

            $session = CashRegisterSession::create([
                'user_id' => $user->id,
                'user_shift_id' => $userShift->id,
                'opening_amount' => $data['opening_amount'],
                'notes' => $data['notes'],
                'opened_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                'created_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
            ]);

            $activeShift = [
                'id' => $userShift->id,
                'date' => Carbon::parse($userShift->date, 'Asia/Ho_Chi_Minh')->format('Y-m-d'),
                'status' => $userShift->status,
                'check_in' => $userShift->check_in ? Carbon::parse($userShift->check_in, 'Asia/Ho_Chi_Minh')->toISOString() : null,
                'opened_at' => $session->opened_at ? $session->opened_at->setTimezone('Asia/Ho_Chi_Minh')->toISOString() : null,
            ];

            return response()->json([
                'success' => 'Ca làm việc đã được mở thành công!',
                'activeShift' => $activeShift,
                'hasActiveSession' => true,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => 'Có lỗi khi mở ca làm việc.']], 500);
        }
    }

    public function closeSession(Request $request)
    {
        try {
            $user = Auth::user();
            if ($user->role_id !== 3) {
                return response()->json(['errors' => ['server' => 'Không có quyền truy cập.']], 403);
            }

            $data = $request->validate([
                'closing_amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:255',
            ]);

            $session = CashRegisterSession::where('user_id', $user->id)
                ->whereNull('closed_at')
                ->whereNull('deleted_at')
                ->first();

            if (!$session) {
                return response()->json(['errors' => ['server' => 'Không có ca làm việc đang mở.']], 422);
            }

            $pendingBills = Bill::where('session_id', $session->id)
                ->where('payment_status_id', 1)
                ->whereNull('deleted_at')
                ->count();

            if ($pendingBills > 0) {
                return response()->json(['errors' => ['server' => 'Vui lòng xử lý tất cả hóa đơn chưa thanh toán trước khi đóng ca.']], 422);
            }

            $userShift = UserShift::find($session->user_shift_id);
            $shiftCheck = $this->checkShiftExpiration($userShift);
            if ($shiftCheck['isExpired']) {
            }

            $bills = Bill::where('session_id', $session->id)
                ->whereNull('deleted_at')
                ->get();

            $actual_amount = $bills->sum('total_amount');
            $difference = $data['closing_amount'] - ($session->opening_amount + $actual_amount);

            $session->update([
                'closing_amount' => $data['closing_amount'],
                'actual_amount' => $actual_amount,
                'difference' => $difference,
                'notes' => $data['notes'],
                'closed_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
            ]);

            if ($userShift) {
                $userShift->update([
                    'status' => 'CHECKED_OUT',
                    'check_out' => Carbon::now('Asia/Ho_Chi_Minh'),
                    'total_hours' => $this->calculateTotalHours($userShift->check_in, Carbon::now('Asia/Ho_Chi_Minh')),
                    'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                ]);
            }

            return response()->json([
                'success' => 'Ca làm việc đã được đóng thành công!',
                'hasActiveSession' => false,
                'difference' => $difference,
                'actual_amount' => $actual_amount,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => 'Có lỗi khi đóng ca làm việc.']], 500);
        }
    }

    public function shiftReport()
    {
        try {
            $user = Auth::user();
            if ($user->role_id !== 3) {
                return response()->json(['errors' => ['server' => 'Không có quyền truy cập.']], 403);
            }

            $session = CashRegisterSession::where('user_id', $user->id)
                ->whereNull('closed_at')
                ->whereNull('deleted_at')
                ->first();

            if (!$session) {
                return response()->json([
                    'hasActiveSession' => false,
                    'session' => null,
                    'report' => null,
                    'message' => 'Không có ca làm việc đang mở.',
                ], 200);
            }

            $userShift = UserShift::find($session->user_shift_id);
            if (!$userShift) {
                return response()->json(['errors' => ['server' => 'Không tìm thấy thông tin ca làm việc.']], 422);
            }

            $shiftCheck = $this->checkShiftExpiration($userShift);
            if ($shiftCheck['isExpired']) {
                return response()->json(['errors' => ['server' => $shiftCheck['message']]], 422);
            }

            $bills = Bill::where('session_id', $session->id)
                ->whereNull('deleted_at')
                ->with(['customer' => function ($query) {
                    $query->select('id', 'customer_name');
                }])
                ->get();

            $billDetails = BillDetail::whereIn('bill_id', $bills->pluck('id'))
                ->select('product_id', DB::raw('SUM(quantity) as total_quantity'))
                ->groupBy('product_id')
                ->with(['product' => function ($query) {
                    $query->select('id', 'name', 'selling_price');
                }])
                ->get();

            $productSales = $billDetails->map(function ($detail) {
                return [
                    'product_id' => $detail->product_id,
                    'product_name' => $detail->product ? $detail->product->name : 'N/A',
                    'quantity_sold' => $detail->total_quantity,
                    'total_revenue' => $detail->total_quantity * ($detail->product->selling_price ?? 0),
                ];
            })->toArray();

            $customerData = $bills->groupBy('customer_id')->map(function ($group) {
                $customer = $group->first()->customer;
                return [
                    'customer_id' => $group->first()->customer_id,
                    'customer_name' => $customer ? $customer->customer_name : 'Khách lẻ',
                    'total_amount' => $group->sum('total_amount'),
                    'bill_count' => $group->count(),
                ];
            })->values()->toArray();

            $report = [
                'total_sales' => $bills->sum('total_amount'),
                'total_cash' => $bills->where('payment_method', 'cash')->sum('total_amount'),
                'total_card' => $bills->where('payment_method', 'card')->sum('total_amount'),
                'total_transfer' => $bills->where('payment_method', 'bank_transfer')->sum('total_amount'),
                'total_wallet' => $bills->where('payment_method', 'wallet')->sum('total_amount'),
                'bill_count' => $bills->count(),
                'customers' => $customerData,
                'product_sales' => $productSales,
                'total_products_sold' => $billDetails->sum('total_quantity'),
            ];

            $sessionData = [
                'id' => $userShift->id,
                'date' => Carbon::parse($userShift->date, 'Asia/Ho_Chi_Minh')->format('Y-m-d'),
                'status' => $userShift->status,
                'check_in' => $userShift->check_in ? Carbon::parse($userShift->check_in, 'Asia/Ho_Chi_Minh')->format('H:i:s') : null,
                'check_out' => $userShift->check_out ? Carbon::parse($userShift->check_out, 'Asia/Ho_Chi_Minh')->format('H:i:s') : null,
                'opened_at' => $session->opened_at ? $session->opened_at->setTimezone('Asia/Ho_Chi_Minh')->toISOString() : null,
                'opening_amount' => $session->opening_amount,
                'closing_amount' => $session->closing_amount ?? 0,
                'actual_amount' => $session->actual_amount ?? 0,
                'difference' => $session->difference ?? 0,
                'closed_at' => $session->closed_at ? $session->closed_at->setTimezone('Asia/Ho_Chi_Minh')->toISOString() : null,
                'notes' => $session->notes ?? 'Không có',
                'default_notes' => $this->defaultNotes,
            ];

            return response()->json([
                'hasActiveSession' => true,
                'session' => $sessionData,
                'report' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => 'Có lỗi khi tạo báo cáo ca.']], 500);
        }
    }

    public function generateShiftReport(Request $request)
    {
        try {
            $user = Auth::user();
            if ($user->role_id !== 3) {
                return response()->json(['errors' => ['server' => 'Không có quyền truy cập.']], 403);
            }

            $data = $request->validate([
                'closing_amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:500',
            ]);

            $session = CashRegisterSession::where('user_id', $user->id)
                ->whereNull('closed_at')
                ->whereNull('deleted_at')
                ->first();

            if (!$session) {
                return response()->json(['errors' => ['server' => 'Không có ca làm việc đang mở.']], 422);
            }

            $userShift = UserShift::find($session->user_shift_id);
            if (!$userShift) {
                return response()->json(['errors' => ['server' => 'Không tìm thấy thông tin ca làm việc.']], 422);
            }

            $shiftCheck = $this->checkShiftExpiration($userShift);
            if ($shiftCheck['isExpired']) {
                return response()->json(['errors' => ['server' => $shiftCheck['message']]], 422);
            }

            $bills = Bill::where('session_id', $session->id)
                ->whereNull('deleted_at')
                ->get();

            $actual_amount = $bills->sum('total_amount');
            $difference = $data['closing_amount'] - ($session->opening_amount + $actual_amount);

            $session->update([
                'actual_amount' => $actual_amount,
                'closing_amount' => $data['closing_amount'],
                'difference' => $difference,
                'notes' => $data['notes'] ?? 'Không có',
                'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
            ]);

            return response()->json([
                'message' => 'Báo cáo ca đã được tạo thành công!',
                'session' => [
                    'actual_amount' => $actual_amount,
                    'difference' => $difference,
                    'notes' => $data['notes'] ?? 'Không có',
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => 'Có lỗi khi tạo báo cáo ca.']], 500);
        }
    }

    public function checkBatch(Request $request, $productId)
    {
        try {
            $quantity = $request->query('quantity', 1);
            $product = Product::find($productId);

            if (!$product || $product->is_active == false || $product->deleted_at) {
                return response()->json([
                    'hasValidBatch' => false,
                    'availableStock' => 0,
                    'message' => 'Sản phẩm không tồn tại hoặc không hoạt động.',
                ], 200);
            }

            $availableStock = $this->calculateAvailableStock($productId, true);

            if ($availableStock == 0) {
                return response()->json([
                    'hasValidBatch' => false,
                    'availableStock' => 0,
                    'message' => "Sản phẩm {$product->name} không có lô hàng hợp lệ.",
                ], 200);
            }

            if ($availableStock < $quantity) {
                return response()->json([
                    'hasValidBatch' => false,
                    'availableStock' => $availableStock,
                    'message' => "Sản phẩm {$product->name} không đủ tồn kho. Chỉ còn {$availableStock}.",
                ], 200);
            }

            $batchItems = BatchItem::where('product_id', $productId)
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
                ->get();

            $hasPartiallyReceived = $batchItems->contains(function ($batchItem) {
                return $batchItem->batch->receipt_status === 'partially_received';
            });

            return response()->json([
                'hasValidBatch' => true,
                'availableStock' => $availableStock,
                'message' => 'Sản phẩm có lô hàng hợp lệ.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => 'Có lỗi khi kiểm tra lô hàng.']], 500);
        }
    }

    public function submitSale(Request $request)
    {
        try {
            $user = Auth::user();
            if ($user->role_id !== 3) {
                return response()->json(['errors' => ['server' => 'Không có quyền truy cập.']], 403);
            }

            $data = $request->validate([
                'cart' => 'required|array|min:1',
                'cart.*.id' => 'required|integer|exists:products,id,deleted_at,NULL,is_active,1',
                'cart.*.quantity' => 'required|integer|min:1',
                'customer_id' => 'nullable|integer|exists:customers,id,deleted_at,NULL',
                'paymentMethod' => 'required|in:cash,card,wallet,bank_transfer,combined',
                'amountReceived' => 'required_if:paymentMethod,cash|numeric|min:0',
                'walletAmount' => 'required_if:paymentMethod,combined|numeric|min:0',
                'cashAmount' => 'required_if:paymentMethod,combined|numeric|min:0',
                'orderNotes' => 'nullable|string|max:255',
                'couponCode' => 'nullable|string|exists:promotions,coupon_code',
                'orderId' => 'required_if:paymentMethod,bank_transfer|string',
            ]);

            $session = CashRegisterSession::where('user_id', $user->id)
                ->whereNull('closed_at')
                ->whereNull('deleted_at')
                ->first();

            if (!$session) {
                return response()->json(['errors' => ['server' => 'Không có ca làm việc đang mở.']], 422);
            }

            $userShift = UserShift::find($session->user_shift_id);
            if (!$userShift) {
                return response()->json(['errors' => ['server' => 'Không tìm thấy thông tin ca làm việc.']], 422);
            }

            $shiftCheck = $this->checkShiftExpiration($userShift);
            if ($shiftCheck['isExpired']) {
                return response()->json(['errors' => ['server' => $shiftCheck['message']]], 422);
            }

            return DB::transaction(function () use ($data, $user, $session) {
                $subTotal = 0;
                $discountAmount = 0;
                $promotion = null;
                $freeItems = [];
                $updatedProducts = [];

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

                $totalAmount = $subTotal - $discountAmount;

                if ($data['paymentMethod'] === 'wallet' || $data['paymentMethod'] === 'combined') {
                    if (!$data['customer_id']) {
                        return response()->json(['errors' => ['customer_id' => 'Vui lòng chọn khách hàng khi thanh toán bằng ví hoặc kết hợp.']], 422);
                    }
                    $customer = Customer::lockForUpdate()->find($data['customer_id']);
                    if (!$customer) {
                        return response()->json(['errors' => ['customer_id' => 'Khách hàng không tồn tại.']], 422);
                    }
                    $walletToUse = $data['paymentMethod'] === 'wallet' ? $totalAmount : ($data['walletAmount'] ?? 0);
                    if ($customer->wallet < $walletToUse) {
                        return response()->json(['errors' => ['payment' => 'Số dư ví không đủ để thanh toán.']], 422);
                    }
                }

                if ($data['paymentMethod'] === 'cash' && ($data['amountReceived'] ?? 0) < $totalAmount) {
                    return response()->json(['errors' => ['payment' => 'Số tiền nhận không đủ để thanh toán.']], 422);
                }

                if ($data['paymentMethod'] === 'combined') {
                    $cashNeeded = $totalAmount - $data['walletAmount'];
                    if ($cashNeeded < 0) {
                        return response()->json(['errors' => ['payment' => 'Số tiền ví vượt quá tổng đơn hàng.']], 422);
                    }
                    if ($data['cashAmount'] < $cashNeeded) {
                        return response()->json(['errors' => ['payment' => 'Số tiền mặt không đủ để thanh toán.']], 422);
                    }
                }

                $receivedMoney = 0;
                $changeMoney = 0;
                $paymentStatusId = 2; // Assuming 2 is paid

                if ($data['paymentMethod'] === 'cash') {
                    $receivedMoney = $data['amountReceived'];
                    $changeMoney = $receivedMoney - $totalAmount;
                } elseif ($data['paymentMethod'] === 'combined') {
                    $receivedMoney = $data['walletAmount'] + $data['cashAmount'];
                    $changeMoney = $data['cashAmount'] - ($totalAmount - $data['walletAmount']);
                    $customer->wallet -= $data['walletAmount'];
                    $customer->save();
                } elseif ($data['paymentMethod'] === 'wallet') {
                    $receivedMoney = $totalAmount;
                    $changeMoney = 0;
                    $paymentStatusId = 1; // Keep as is
                    $customer->wallet -= $totalAmount;
                    $customer->save();
                } else {
                    $receivedMoney = $totalAmount;
                    $changeMoney = 0;
                }

                $bill = Bill::create([
                    'bill_number' => $data['orderId'] ?? 'BILL-' . date('YmdHis') . '-' . rand(1000, 9999),
                    'customer_id' => $data['customer_id'],
                    'sub_total' => $subTotal,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'received_money' => $receivedMoney,
                    'change_money' => $changeMoney,
                    'payment_method' => $data['paymentMethod'],
                    'payment_status_id' => $paymentStatusId,
                    'notes' => $data['orderNotes'],
                    'cashier_id' => $user->id,
                    'session_id' => $session->id,
                    'created_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                    'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                ]);

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
                                'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                            ]);

                            InventoryTransaction::create([
                                'transaction_type_id' => 2,
                                'product_id' => $product->id,
                                'quantity_change' => -$quantityToDeduct,
                                'stock_after' => $product->stock_quantity - $quantityToDeduct,
                                'unit_price' => $product->selling_price,
                                'total_value' => $quantityToDeduct * $product->selling_price,
                                'transaction_date' => Carbon::now('Asia/Ho_Chi_Minh'),
                                'related_bill_id' => $bill->id,
                                'related_batch_id' => $batchItem->batch_id,
                                'user_id' => $user->id,
                                'created_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                                'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
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
                                'created_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                                'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                            ]);

                            $remainingQuantity -= $quantityToDeduct;
                        }
                    }

                    if ($remainingQuantity > 0) {
                        throw new \Exception("Không đủ hàng trong kho cho sản phẩm {$product->name}.");
                    }

                    $product->stock_quantity -= $item['quantity'];
                    $product->last_sold_at = Carbon::now('Asia/Ho_Chi_Minh');
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
                                'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                            ]);

                            InventoryTransaction::create([
                                'transaction_type_id' => 2,
                                'product_id' => $product->id,
                                'quantity_change' => -$quantityToDeduct,
                                'stock_after' => $product->stock_quantity - $quantityToDeduct,
                                'unit_price' => 0,
                                'total_value' => 0,
                                'transaction_date' => Carbon::now('Asia/Ho_Chi_Minh'),
                                'related_bill_id' => $bill->id,
                                'related_batch_id' => $batchItem->batch_id,
                                'user_id' => $user->id,
                                'created_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                                'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
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
                                'created_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                                'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                            ]);

                            $remainingQuantity -= $quantityToDeduct;
                        }
                    }

                    $product->stock_quantity -= $freeItem['quantity'];
                    $product->last_sold_at = Carbon::now('Asia/Ho_Chi_Minh');
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

                if ($data['customer_id']) {
                    $customer = Customer::lockForUpdate()->find($data['customer_id']);
                    $walletBonus = $totalAmount * 0.001;
                    $customer->wallet += $walletBonus;
                    
                    $customer->save();
                }

                if ($promotion) {
                    $promotion->increment('usage_count');
                }

                $session->actual_amount = ($session->actual_amount ?? 0) + $totalAmount;
                $session->save();

                if ($data['customer_id']) {
                    $customer = Customer::find($data['customer_id']);
                    if ($customer->email) {
                        try {
                            Mail::to($customer->email)->queue(new ReceiptEmail($bill));
                        } catch (\Exception $e) {
                        }
                    }
                }

                return response()->json([
                    'success' => 'Thanh toán thành công!',
                    'bill' => [
                        'id' => $bill->id,
                        'bill_number' => $bill->bill_number,
                        'total_amount' => $bill->total_amount,
                        'created_at' => $bill->created_at->setTimezone('Asia/Ho_Chi_Minh')->toISOString(),
                    ],
                    'products' => $updatedProducts,
                    'hasActiveSession' => true,
                ], 200);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => 'Có lỗi xảy ra khi xử lý giao dịch: ' . $e->getMessage()]], 500);
        }
    }

    public function syncInventory()
    {
        try {
            $products = Product::where('is_active', true)
                ->whereNull('deleted_at')
                ->get();

            foreach ($products as $product) {
                $batchQuantity = BatchItem::where('product_id', $product->id)
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
                    ->sum('current_quantity');

                if ($product->stock_quantity != $batchQuantity) {
                    $product->stock_quantity = $batchQuantity;
                    $product->save();
                }
            }

            return response()->json(['message' => 'Đồng bộ tồn kho hoàn tất!'], 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => 'Có lỗi khi đồng bộ tồn kho.']], 500);
        }
    }

    public function createCustomer(Request $request)
    {
        try {
            $data = $request->validate([
                'customer_name' => 'required|string|max:255',
                'phone' => 'required|string|max:20|unique:customers,phone,NULL,id,deleted_at,NULL',
                'email' => 'nullable|email|max:255|unique:customers,email,NULL,id,deleted_at,NULL',
                'address' => 'nullable|string|max:255',
                'wallet' => 'nullable|numeric|min:0',
            ]);

            $customer = Customer::create([
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'address' => $data['address'],
                'wallet' => $data['wallet'] ?? 0,
                'created_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
            ]);

            $newCustomer = [
                'id' => $customer->id,
                'customer_name' => $customer->customer_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'wallet' => $customer->wallet ?? 0,
            ];

            return response()->json([
                'success' => 'Khách hàng đã được thêm thành công!',
                'newCustomer' => $newCustomer,
                'customers' => $this->getCustomers(),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => 'Có lỗi khi thêm khách hàng.']], 500);
        }
    }

    public function getCustomersPublic(Request $request)
    {
        try {
            $customers = $this->getCustomers();

            return response()->json([
                'customers' => $customers,
                'success' => 'Danh sách khách hàng đã được tải thành công.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => 'Lỗi tải danh sách khách hàng: ' . $e->getMessage()]], 500);
        }
    }

    public function getProductByBarcode($barcode)
    {
        $product = Product::where('barcode', $barcode)->first();
        if (!$product) {
            return response()->json(['errors' => ['server' => 'Không tìm thấy sản phẩm với mã vạch này.']], 404);
        }
        
 
    return response()->json([
        'product' => [
            'id' => $product->id,
            'name' => $product->name,
            'barcode' => $product->barcode,
            'price' => $product->selling_price ?? 0, 
            'stock_quantity' => $product->stock_quantity,
            'image' => $product->image_url ?? '/storage/piclumen-1747750187180.png',
            'sku' => $product->sku,
        ]
    ]);
}


    private function getProducts()
    {
        return Product::with('category')
            ->select('id', 'name', 'category_id', 'selling_price', 'image_url', 'sku', 'barcode', 'stock_quantity')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($product) {
                $availableStock = $this->getAvailableStock($product->id);
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category ? $product->category->name : 'Chưa phân loại',
                    'price' => $product->selling_price ?? 0,
                    'stock' => $availableStock,
                    'image' => $product->image_url ?? '/storage/piclumen-1747750187180.png',
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                ];
            })->toArray();
    }

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
    public function listCustomers()
    {
        return response()->json([
            'customers' => $this->getCustomers()
        ]);
    }

    private function getCustomers()
    {
        return Customer::select('id', 'customer_name', 'email', 'phone', 'address', 'wallet')
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'customer_name' => $customer->customer_name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'address' => $customer->address,
                    'wallet' => $customer->wallet ?? 0,
                ];
            })->toArray();
    }

    private function hasActiveSession($user)
    {
        return CashRegisterSession::where('user_id', $user->id)
            ->whereNull('closed_at')
            ->whereNull('deleted_at')
            ->exists();
    }

    private function getActiveShift($user)
    {
        $session = CashRegisterSession::select('id', 'user_id', 'user_shift_id', 'opened_at')
            ->where('user_id', $user->id)
            ->whereNull('closed_at')
            ->whereNull('deleted_at')
            ->first();

        if (!$session) {
            return null;
        }

        $userShift = UserShift::where('id', $session->user_shift_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$userShift) {
            return null;
        }

        return [
            'id' => $userShift->id,
            'date' => Carbon::parse($userShift->date, 'Asia/Ho_Chi_Minh')->format('Y-m-d'),
            'status' => $userShift->status,
            'check_in' => $userShift->check_in ? Carbon::parse($userShift->check_in, 'Asia/Ho_Chi_Minh')->toISOString() : null,
            'opened_at' => $session->opened_at ? $session->opened_at->setTimezone('Asia/Ho_Chi_Minh')->toISOString() : null,
        ];
    }

    private function calculateTotalHours($checkIn, $checkOut)
    {
        if ($checkIn && $checkOut) {
            $checkInTime = Carbon::parse($checkIn);
            $checkOutTime = Carbon::parse($checkOut);
            return round($checkInTime->diffInHours($checkOutTime, true), 2);
        }
        return null;
    }
    
}
