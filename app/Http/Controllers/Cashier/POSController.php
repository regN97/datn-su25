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
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\CashRegisterSession;
use Illuminate\Support\Facades\Log;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
        if ($user->role_id !== 3) { // CASHIER role
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
                'image' => $product->image_url ?? '/storage/default-product.png',
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
                Log::warning('Đồng bộ stock_quantity cho sản phẩm.', [
                    'product_id' => $product->id,
                    'current_stock' => $product->stock_quantity,
                    'batch_quantity' => $batchQuantity,
                ]);
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
            Log::warning('Ca làm việc đã hết hạn.', [
                'user_shift_id' => $userShift->id,
                'status' => $userShift->status,
                'current_time' => $currentTime->toISOString(),
                'check_out' => $userShift->check_out ? Carbon::parse($userShift->check_out, 'Asia/Ho_Chi_Minh')->toISOString() : null,
                'user_id' => Auth::id(),
            ]);
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
                Log::warning('Không có ca làm việc nào trong cơ sở dữ liệu.', ['user_id' => Auth::id()]);
                return response()->json(['shifts' => [], 'message' => 'Không có ca làm việc được cấu hình.']);
            }

            return response()->json(['shifts' => $userShifts]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách ca làm việc.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
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
                Log::warning('Nhân viên đã có ca làm việc đang mở.', [
                    'user_id' => $user->id,
                    'user_shift_id' => $existingShift->id,
                ]);
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

            Log::info('Mở phiên làm việc mới thành công.', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'user_shift_id' => $userShift->id,
            ]);

            return response()->json([
                'success' => 'Ca làm việc đã được mở thành công!',
                'activeShift' => $activeShift,
                'hasActiveSession' => true,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Lỗi xác thực khi mở phiên làm việc.', [
                'errors' => $e->errors(),
                'user_id' => Auth::id(),
            ]);
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Lỗi khi mở phiên làm việc.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
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
                ->where('payment_status_id', 1) // UNPAID
                ->whereNull('deleted_at')
                ->count();

            if ($pendingBills > 0) {
                Log::warning('Có hóa đơn chưa thanh toán trong ca.', [
                    'session_id' => $session->id,
                    'pending_bills' => $pendingBills,
                    'user_id' => $user->id,
                ]);
                return response()->json(['errors' => ['server' => 'Vui lòng xử lý tất cả hóa đơn chưa thanh toán trước khi đóng ca.']], 422);
            }

            $userShift = UserShift::find($session->user_shift_id);
            $shiftCheck = $this->checkShiftExpiration($userShift);
            if ($shiftCheck['isExpired']) {
                Log::info('Đóng ca làm việc đã hết hạn.', [
                    'session_id' => $session->id,
                    'user_shift_id' => $session->user_shift_id,
                    'user_id' => $user->id,
                ]);
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

            Log::info('Đóng phiên làm việc thành công.', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'difference' => $difference,
            ]);

            return response()->json([
                'success' => 'Ca làm việc đã được đóng thành công!',
                'hasActiveSession' => false,
                'difference' => $difference,
                'actual_amount' => $actual_amount,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Lỗi xác thực khi đóng phiên làm việc.', [
                'errors' => $e->errors(),
                'user_id' => Auth::id(),
            ]);
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Lỗi khi đóng phiên làm việc.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
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
                Log::warning('Không tìm thấy thông tin ca làm việc cho báo cáo.', [
                    'session_id' => $session->id,
                    'user_shift_id' => $session->user_shift_id,
                    'user_id' => $user->id,
                ]);
                return response()->json(['errors' => ['server' => 'Không tìm thấy thông tin ca làm việc.']], 422);
            }

            $shiftCheck = $this->checkShiftExpiration($userShift);
            if ($shiftCheck['isExpired']) {
                Log::warning('Yêu cầu báo cáo ca khi ca đã hết hạn.', [
                    'user_shift_id' => $userShift->id,
                    'status' => $userShift->status,
                    'user_id' => $user->id,
                ]);
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
            Log::error('Lỗi khi tạo báo cáo ca làm việc.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
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
                Log::warning('Không tìm thấy thông tin ca làm việc khi tạo báo cáo.', [
                    'session_id' => $session->id,
                    'user_shift_id' => $session->user_shift_id,
                    'user_id' => $user->id,
                ]);
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

            Log::info('Báo cáo ca làm việc đã được tạo.', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'actual_amount' => $actual_amount,
                'difference' => $difference,
                'notes' => $data['notes'] ?? 'Không có',
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
            Log::error('Lỗi xác thực khi tạo báo cáo ca.', [
                'errors' => $e->errors(),
                'user_id' => Auth::id(),
            ]);
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Lỗi khi tạo báo cáo ca.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            return response()->json(['errors' => ['server' => 'Có lỗi khi tạo báo cáo ca.']], 500);
        }
    }

    public function checkBatch(Request $request, $productId)
    {
        try {
            $quantity = $request->query('quantity', 1);
            $product = Product::find($productId);

            if (!$product || $product->is_active == false || $product->deleted_at) {
                Log::warning('Sản phẩm không tồn tại hoặc không hoạt động.', ['product_id' => $productId]);
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

            if ($hasPartiallyReceived) {
                Log::info('Sản phẩm có lô partially_received khả dụng.', [
                    'product_id' => $productId,
                    'available_stock' => $availableStock,
                    'user_id' => Auth::id(),
                ]);
            }

            return response()->json([
                'hasValidBatch' => true,
                'availableStock' => $availableStock,
                'message' => 'Sản phẩm có lô hàng hợp lệ.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Lỗi khi kiểm tra lô hàng.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'product_id' => $productId,
                'user_id' => Auth::id(),
            ]);
            return response()->json(['errors' => ['server' => 'Có lỗi khi kiểm tra lô hàng.']], 500);
        }
    }

    public function submitSale(Request $request)
    {
        try {
            $user = Auth::user();
            if ($user->role_id !== 3) {
                Log::warning('Không có quyền truy cập.', ['user_id' => $user->id]);
                return response()->json(['errors' => ['server' => 'Không có quyền truy cập.']], 403);
            }

            Log::info('Dữ liệu giao dịch bán hàng.', ['data' => $request->all(), 'user_id' => $user->id]);

            $data = $request->validate([
                'cart' => 'required|array|min:1',
                'cart.*.id' => 'required|integer|exists:products,id,deleted_at,NULL,is_active,1',
                'cart.*.quantity' => 'required|integer|min:1',
                'customer_id' => 'nullable|integer|exists:customers,id,deleted_at,NULL',
                'paymentMethod' => 'required|in:cash,card,wallet,bank_transfer',
                'amountReceived' => 'required_if:paymentMethod,cash|numeric|min:0',
                'orderNotes' => 'nullable|string|max:255',
                'couponCode' => 'nullable|string|exists:promotions,coupon_code',
                'orderId' => 'required_if:paymentMethod,bank_transfer|string',
            ], [
                'cart.required' => 'Giỏ hàng không được để trống.',
                'cart.min' => 'Giỏ hàng phải chứa ít nhất một sản phẩm.',
                'cart.*.id.exists' => 'Sản phẩm ID :input không tồn tại hoặc không hoạt động.',
                'cart.*.quantity.min' => 'Số lượng sản phẩm phải lớn hơn 0.',
                'paymentMethod.in' => 'Phương thức thanh toán không hợp lệ.',
                'amountReceived.required_if' => 'Vui lòng nhập số tiền nhận khi thanh toán bằng tiền mặt.',
                'orderId.required_if' => 'Mã đơn hàng (orderId) là bắt buộc khi thanh toán bằng chuyển khoản.',
            ]);

            $session = CashRegisterSession::where('user_id', $user->id)
                ->whereNull('closed_at')
                ->whereNull('deleted_at')
                ->first();

            if (!$session) {
                Log::warning('Không có ca làm việc đang mở.', ['user_id' => $user->id]);
                return response()->json(['errors' => ['server' => 'Không có ca làm việc đang mở.']], 422);
            }

            $userShift = UserShift::find($session->user_shift_id);
            if (!$userShift) {
                Log::warning('Không tìm thấy thông tin ca làm việc.', [
                    'session_id' => $session->id,
                    'user_shift_id' => $session->user_shift_id,
                    'user_id' => $user->id,
                ]);
                return response()->json(['errors' => ['server' => 'Không tìm thấy thông tin ca làm việc.']], 422);
            }

            $shiftCheck = $this->checkShiftExpiration($userShift);
            if ($shiftCheck['isExpired']) {
                Log::warning('Ca làm việc đã hết hạn.', [
                    'user_shift_id' => $userShift->id,
                    'user_id' => $user->id,
                ]);
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
                        Log::warning('Mã khuyến mãi không hợp lệ hoặc đã hết hạn.', ['coupon_code' => $data['couponCode']]);
                        return response()->json(['errors' => ['couponCode' => 'Mã khuyến mãi không hợp lệ hoặc đã hết hạn.']], 422);
                    }
                }

                $productIds = array_column($data['cart'], 'id');
                $stocks = $this->calculateAvailableStock($productIds);

                foreach ($data['cart'] as $item) {
                    $product = Product::find($item['id']);
                    if (!$product) {
                        Log::warning('Sản phẩm không tồn tại.', ['product_id' => $item['id']]);
                        return response()->json(['errors' => ['cart' => "Sản phẩm ID {$item['id']} không tồn tại."]], 422);
                    }

                    $availableStock = $stocks[$item['id']] ?? 0;
                    if ($availableStock < $item['quantity']) {
                        Log::warning('Sản phẩm không đủ tồn kho.', [
                            'product_id' => $item['id'],
                            'requested_quantity' => $item['quantity'],
                            'available_quantity' => $availableStock,
                        ]);
                        return response()->json(['errors' => ['cart' => "Sản phẩm {$product->name} không đủ tồn kho. Chỉ còn {$availableStock}."]], 422);
                    }

                    $subTotal += $item['quantity'] * $product->selling_price;
                }

                if ($promotion) {
                    if ($promotion->type_id == 1) { // FIXED_AMOUNT
                        $discountAmount = $promotion->discount_value;
                    } elseif ($promotion->type_id == 2) { // PERCENTAGE
                        $discountAmount = ($promotion->discount_value / 100) * $subTotal;
                    } elseif ($promotion->type_id == 4 && $subTotal >= $promotion->min_order_value) { // ORDER_DISCOUNT
                        $discountAmount = $promotion->discount_value;
                    } elseif ($promotion->type_id == 3) { // BUY_X_GET_Y
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

                if ($data['paymentMethod'] === 'wallet') {
                    if (!$data['customer_id']) {
                        Log::warning('Không có customer_id khi thanh toán bằng ví.', ['user_id' => $user->id]);
                        return response()->json(['errors' => ['customer_id' => 'Vui lòng chọn khách hàng khi thanh toán bằng ví.']], 422);
                    }
                    $customer = Customer::lockForUpdate()->find($data['customer_id']);
                    if (!$customer || $customer->wallet < $totalAmount) {
                        Log::warning('Số dư ví không đủ.', [
                            'customer_id' => $data['customer_id'],
                            'wallet' => $customer->wallet ?? 0,
                            'total_amount' => $totalAmount,
                        ]);
                        return response()->json(['errors' => ['payment' => 'Số dư ví không đủ để thanh toán.']], 422);
                    }
                } elseif ($data['paymentMethod'] === 'cash' && $data['amountReceived'] < $totalAmount) {
                    Log::warning('Số tiền nhận không đủ.', [
                        'amount_received' => $data['amountReceived'],
                        'total_amount' => $totalAmount,
                    ]);
                    return response()->json(['errors' => ['payment' => 'Số tiền nhận không đủ để thanh toán.']], 422);
                }

                $bill = Bill::create([
                    'bill_number' => $data['orderId'] ?? 'BILL-' . date('YmdHis') . '-' . rand(1000, 9999),
                    'customer_id' => $data['customer_id'],
                    'sub_total' => $subTotal,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'received_money' => $data['paymentMethod'] === 'cash' ? $data['amountReceived'] : $totalAmount,
                    'change_money' => $data['paymentMethod'] === 'cash' ? ($data['amountReceived'] - $totalAmount) : 0,
                    'payment_method' => $data['paymentMethod'],
                    'payment_status_id' => $data['paymentMethod'] === 'wallet' ? 1 : 2, // 1: UNPAID, 2: PAID
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
                        Log::error('Không tìm thấy lô hàng hợp lệ cho sản phẩm.', [
                            'product_id' => $item['id'],
                            'requested_quantity' => $item['quantity'],
                        ]);
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

                            if ($batchItem->batch->receipt_status === 'partially_received') {
                                Log::info('Sử dụng lô partially_received cho giao dịch bán hàng.', [
                                    'batch_id' => $batchItem->batch_id,
                                    'product_id' => $item['id'],
                                    'quantity_deducted' => $quantityToDeduct,
                                    'user_id' => $user->id,
                                ]);
                            }

                            InventoryTransaction::create([
                                'transaction_type_id' => 2, // Xuất kho
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
                        Log::error('Không đủ hàng trong kho để phân bổ.', [
                            'product_id' => $item['id'],
                            'remaining_quantity' => $remainingQuantity,
                        ]);
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
                        'image' => $product->image_url ?? '/storage/default-product.png',
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
                        Log::error('Không tìm thấy lô hàng hợp lệ cho sản phẩm miễn phí.', [
                            'product_id' => $freeItem['product_id'],
                            'requested_quantity' => $freeItem['quantity'],
                        ]);
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
                                'transaction_type_id' => 2, // Xuất kho
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
                        'image' => $product->image_url ?? '/storage/default-product.png',
                        'sku' => $product->sku,
                        'barcode' => $product->barcode,
                    ];
                }

                if ($data['customer_id']) {
                    $customer = Customer::lockForUpdate()->find($data['customer_id']);
                    $walletBonus = $totalAmount * 0.001;
                    $customer->wallet += $walletBonus;
                    if ($data['paymentMethod'] === 'wallet') {
                        $customer->wallet -= $totalAmount;
                    }
                    $customer->save();

                    Log::info('Cập nhật ví khách hàng.', [
                        'customer_id' => $customer->id,
                        'wallet_bonus' => $walletBonus,
                        'new_wallet_balance' => $customer->wallet,
                        'payment_method' => $data['paymentMethod'],
                    ]);
                }

                if ($promotion) {
                    $promotion->increment('usage_count');
                    Log::info('Cập nhật số lần sử dụng khuyến mãi.', [
                        'promotion_id' => $promotion->id,
                        'coupon_code' => $promotion->coupon_code,
                        'usage_count' => $promotion->usage_count,
                    ]);
                }

                $session->actual_amount = ($session->actual_amount ?? 0) + $totalAmount;
                $session->save();

                if ($data['customer_id']) {
                    $customer = Customer::find($data['customer_id']);
                    if ($customer->email) {
                        try {
                            Mail::to($customer->email)->queue(new ReceiptEmail($bill));
                            Log::info('Đã gửi email hóa đơn.', [
                                'bill_id' => $bill->id,
                                'customer_id' => $customer->id,
                                'email' => $customer->email,
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Lỗi khi gửi email hóa đơn.', [
                                'bill_id' => $bill->id,
                                'customer_id' => $customer->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                Log::info('Giao dịch bán hàng thành công.', [
                    'bill_id' => $bill->id,
                    'total_amount' => $totalAmount,
                    'user_id' => $user->id,
                    'promotion_id' => $promotion ? $promotion->id : null,
                ]);

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
            Log::error('Lỗi xác thực khi xử lý giao dịch bán hàng.', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
            ]);
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Lỗi khi xử lý giao dịch bán hàng.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
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
                    Log::info('Đồng bộ tồn kho cho sản phẩm.', [
                        'product_id' => $product->id,
                        'old_stock_quantity' => $product->stock_quantity,
                        'new_stock_quantity' => $batchQuantity,
                    ]);
                    $product->stock_quantity = $batchQuantity;
                    $product->save();
                }
            }

            return response()->json(['message' => 'Đồng bộ tồn kho hoàn tất!'], 200);
        } catch (\Exception $e) {
            Log::error('Lỗi khi đồng bộ tồn kho.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
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

            Log::info('Tạo khách hàng mới thành công.', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->customer_name,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => 'Khách hàng đã được thêm thành công!',
                'newCustomer' => $newCustomer,
                'customers' => $this->getCustomers(),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Lỗi xác thực khi tạo khách hàng.', [
                'errors' => $e->errors(),
                'user_id' => Auth::id(),
            ]);
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Lỗi khi tạo khách hàng.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            return response()->json(['errors' => ['server' => 'Có lỗi khi thêm khách hàng.']], 500);
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
            'image' => $product->image_url ?? '/storage/default-product.png',
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
                    'image' => $product->image_url ?? '/storage/default-product.png',
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                ];
            })->toArray();
    }

    public function getProductsPublic(Request $request)
    {
        try {
            // Gọi phương thức getProducts hiện có để lấy danh sách sản phẩm
            $products = $this->getProducts();

            return response()->json([
                'products' => $products,
                'success' => 'Danh sách sản phẩm đã được tải thành công.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Lỗi khi lấy danh sách sản phẩm.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            return response()->json(['errors' => ['server' => 'Lỗi tải danh sách sản phẩm: ' . $e->getMessage()]], 500);
        }
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
            Log::info('Không tìm thấy phiên làm việc đang mở.', ['user_id' => $user->id]);
            return null;
        }

        $userShift = UserShift::where('id', $session->user_shift_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$userShift) {
            Log::warning('Không tìm thấy thông tin ca làm việc.', [
                'session_id' => $session->id,
                'user_shift_id' => $session->user_shift_id,
                'user_id' => $user->id,
            ]);
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
