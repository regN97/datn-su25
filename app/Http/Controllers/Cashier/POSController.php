<?php

namespace App\Http\Controllers\Cashier;

use Carbon\Carbon;
use App\Models\Bill;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\Customer;
use App\Models\BatchItem;
use App\Models\Promotion;
use App\Models\WorkShift;
use App\Models\BillDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CashRegisterSession;
use Illuminate\Support\Facades\Log;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\Auth;

class POSController
{
    /**
     * Hiển thị giao diện POS với dữ liệu sản phẩm, khách hàng, phiên làm việc và ca làm việc.
     */
    public function index()
    {
        $user = Auth::user();
        if ($user->role_id !== 3) {
            return redirect()->route('login')->withErrors(['server' => 'Không có quyền truy cập.']);
        }

        $products = Product::with('category')
            ->select('id', 'name', 'category_id', 'selling_price', 'image_url', 'sku', 'barcode')
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->get();

        $productIds = $products->pluck('id')->toArray();
        $stocks = $this->getAvailableStocks($productIds);

        $products = $products->map(function ($product) use ($stocks) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category ? $product->category->name : 'N/A',
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
            $userShift = \App\Models\UserShift::with('workShift')
                ->where('id', $activeSession->user_shift_id)
                ->first();
            if ($userShift && $userShift->workShift) {
                $activeShift = [
                    'shift_id' => $userShift->workShift->id,
                    'shift_name' => $userShift->workShift->name,
                    'shift_time' => [
                        'start_time' => Carbon::parse($userShift->workShift->start_time, 'Asia/Ho_Chi_Minh')->format('H:i:s'),
                        'end_time' => Carbon::parse($userShift->workShift->end_time, 'Asia/Ho_Chi_Minh')->format('H:i:s'),
                    ],
                    'opened_at' => $activeSession->opened_at ? $activeSession->opened_at->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') : null,
                ];
            }
        }

        $currentTime = Carbon::now('Asia/Ho_Chi_Minh')->format('H:i:s');
        $workShifts = WorkShift::select('id', 'name', 'start_time', 'end_time')
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($shift) use ($currentTime) {
                $startTime = Carbon::parse($shift->start_time, 'Asia/Ho_Chi_Minh')->format('H:i:s');
                $endTime = Carbon::parse($shift->end_time, 'Asia/Ho_Chi_Minh')->format('H:i:s');
                $isSuitable = $currentTime >= $startTime && $currentTime <= $endTime;
                if ($startTime > $endTime) {
                    $isSuitable = $currentTime >= $startTime || $currentTime <= $endTime;
                }
                return [
                    'id' => $shift->id,
                    'name' => $shift->name,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'is_suitable' => $isSuitable,
                ];
            })->toArray();

        return Inertia::render('cashier/pos/POS', [
            'products' => $products,
            'customers' => $customers,
            'hasActiveSession' => !!$activeSession,
            'activeShift' => $activeShift,
            'workShifts' => $workShifts,
            'flash' => [
                'success' => session('success'),
            ],
            'errors' => session('errors') ? session('errors')->getBag('default') : null,
        ]);
    }

    /**
     * Lấy danh sách sản phẩm với thông tin giá, tồn kho và danh mục.
     */
    private function getProducts()
    {
        $products = Product::with('category')
            ->select('id', 'name', 'category_id', 'selling_price', 'image_url', 'sku', 'barcode')
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->get();

        $productIds = $products->pluck('id')->toArray();
        $stocks = $this->getAvailableStocks($productIds);

        return $products->map(function ($product) use ($stocks) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category ? $product->category->name : 'N/A',
                'price' => $product->selling_price ?? 0,
                'stock' => $stocks[$product->id] ?? 0,
                'image' => $product->image_url ?? '/storage/default-product.png',
                'sku' => $product->sku,
                'barcode' => $product->barcode,
            ];
        })->toArray();
    }

    /**
     * Lấy danh sách khách hàng với thông tin ví.
     */
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

    /**
     * Lấy danh sách ca làm việc với trạng thái phù hợp theo thời gian hiện tại.
     */
    private function getWorkShiftsData()
    {
        $currentTime = Carbon::now('Asia/Ho_Chi_Minh')->format('H:i:s');
        return WorkShift::select('id', 'name', 'start_time', 'end_time')
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($shift) use ($currentTime) {
                $startTime = Carbon::parse($shift->start_time, 'Asia/Ho_Chi_Minh')->format('H:i:s');
                $endTime = Carbon::parse($shift->end_time, 'Asia/Ho_Chi_Minh')->format('H:i:s');
                $isSuitable = $currentTime >= $startTime && $currentTime <= $endTime;
                if ($startTime > $endTime) {
                    $isSuitable = $currentTime >= $startTime || $currentTime <= $endTime;
                }
                return [
                    'id' => $shift->id,
                    'name' => $shift->name,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'is_suitable' => $isSuitable,
                ];
            })->toArray();
    }

    /**
     * Kiểm tra xem có phiên làm việc đang mở không.
     */
    private function hasActiveSession($user)
    {
        return CashRegisterSession::where('user_id', $user->id)
            ->whereNull('closed_at')
            ->whereNull('deleted_at')
            ->exists();
    }

    /**
     * Lấy thông tin ca làm việc hiện tại.
     */
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

        if (!$session->user_shift_id) {
            Log::warning('Phiên làm việc không có user_shift_id.', [
                'session_id' => $session->id,
                'user_id' => $user->id
            ]);
            return null;
        }

        $userShift = \App\Models\UserShift::with('workShift')
            ->where('id', $session->user_shift_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$userShift) {
            Log::warning('Không tìm thấy UserShift cho session.', [
                'session_id' => $session->id,
                'user_shift_id' => $session->user_shift_id,
                'user_id' => $user->id
            ]);
            return null;
        }

        if (!$userShift->workShift) {
            Log::warning('UserShift không có WorkShift liên kết.', [
                'user_shift_id' => $userShift->id,
                'user_id' => $user->id
            ]);
            return null;
        }

        return [
            'shift_id' => $userShift->workShift->id,
            'shift_name' => $userShift->workShift->name,
            'shift_time' => [
                'start_time' => Carbon::parse($userShift->workShift->start_time, 'Asia/Ho_Chi_Minh')->format('H:i:s'),
                'end_time' => Carbon::parse($userShift->workShift->end_time, 'Asia/Ho_Chi_Minh')->format('H:i:s'),
            ],
            'opened_at' => $session->opened_at ? $session->opened_at->setTimezone('Asia/Ho_Chi_Minh')->toISOString() : null,
        ];
    }

    /**
     * Tính số lượng tồn kho khả dụng cho một sản phẩm.
     */
    private function getAvailableStock($productId)
    {
        $stock = BatchItem::where('product_id', $productId)
            ->where('inventory_status', 'active')
            ->where('current_quantity', '>', 0)
            ->whereHas('batch', function ($query) {
                $query->whereNull('deleted_at')
                      ->where('receipt_status', 'completed');
            })
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>=', Carbon::today('Asia/Ho_Chi_Minh'));
            })
            ->sum('current_quantity');

        Log::info('Tính tồn kho cho sản phẩm', [
            'product_id' => $productId,
            'stock' => $stock
        ]);

        return $stock;
    }

    /**
     * Tính tồn kho cho nhiều sản phẩm cùng lúc để tối ưu hiệu suất.
     */
    private function getAvailableStocks(array $productIds)
    {
        return BatchItem::whereIn('product_id', $productIds)
            ->where('inventory_status', 'active')
            ->where('current_quantity', '>', 0)
            ->whereHas('batch', function ($query) {
                $query->whereNull('deleted_at')
                      ->where('receipt_status', 'completed');
            })
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>=', Carbon::today('Asia/Ho_Chi_Minh'));
            })
            ->groupBy('product_id')
            ->select('product_id', DB::raw('SUM(current_quantity) as total_stock'))
            ->pluck('total_stock', 'product_id')
            ->toArray();
    }

    /**
     * Kiểm tra xem ca làm việc có hết hạn không.
     */
    private function checkShiftExpiration($userShift)
    {
        if (!$userShift || !$userShift->workShift) {
            return ['isExpired' => true, 'message' => 'Không tìm thấy ca làm việc hoặc ca không hợp lệ.'];
        }

        $currentTime = Carbon::now('Asia/Ho_Chi_Minh')->format('H:i:s');
        $startTime = Carbon::parse($userShift->workShift->start_time, 'Asia/Ho_Chi_Minh')->format('H:i:s');
        $endTime = Carbon::parse($userShift->workShift->end_time, 'Asia/Ho_Chi_Minh')->format('H:i:s');

        $isExpired = ($startTime <= $endTime && $currentTime > $endTime) ||
                     ($startTime > $endTime && $currentTime > $endTime && $currentTime < $startTime);

        if ($isExpired) {
            Log::warning('Ca làm việc đã hết hạn', [
                'shift_id' => $userShift->workShift->id,
                'shift_name' => $userShift->workShift->name,
                'current_time' => $currentTime,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'user_id' => Auth::id(),
            ]);
            return ['isExpired' => true, 'message' => 'Ca làm việc đã hết hạn. Vui lòng đóng ca và mở ca mới.'];
        }

        return ['isExpired' => false, 'message' => ''];
    }

    /**
     * Lấy danh sách ca làm việc.
     */
    public function getWorkShifts()
    {
        try {
            $workShifts = $this->getWorkShiftsData();
            if (empty($workShifts)) {
                Log::warning('Không có ca làm việc nào trong cơ sở dữ liệu.', ['user_id' => Auth::id()]);
                return response()->json(['shifts' => [], 'message' => 'Không có ca làm việc được cấu hình.']);
            }
            return response()->json(['shifts' => $workShifts]);
        } catch (\Exception $e) {
            Log::error('Lỗi trong getWorkShifts: ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            return response()->json(['errors' => ['server' => 'Không thể tải danh sách ca làm việc.']], 500);
        }
    }

    /**
     * Mở một phiên làm việc mới.
     */
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
                'shift_id' => 'required|exists:work_shifts,id',
            ]);

            $activeSession = CashRegisterSession::where('user_id', $user->id)
                ->whereNull('closed_at')
                ->whereNull('deleted_at')
                ->first();

            if ($activeSession) {
                return response()->json(['errors' => ['server' => 'Bạn đã có một ca làm việc đang mở.']], 422);
            }

            $workShift = WorkShift::find($data['shift_id']);
            if (!$workShift || $workShift->deleted_at) {
                return response()->json(['errors' => ['server' => 'Ca làm việc không tồn tại hoặc đã bị xóa.']], 422);
            }

            $currentTime = Carbon::now('Asia/Ho_Chi_Minh')->format('H:i:s');
            $startTime = Carbon::parse($workShift->start_time, 'Asia/Ho_Chi_Minh')->format('H:i:s');
            $endTime = Carbon::parse($workShift->end_time, 'Asia/Ho_Chi_Minh')->format('H:i:s');
            $isSuitable = $currentTime >= $startTime && $currentTime <= $endTime;
            if ($startTime > $endTime) {
                $isSuitable = $currentTime >= $startTime || $currentTime <= $endTime;
            }

            if (!$isSuitable) {
                return response()->json(['errors' => ['server' => 'Ca làm việc này không phù hợp với thời gian hiện tại.']], 422);
            }

            $userShift = \App\Models\UserShift::create([
                'user_id' => $user->id,
                'shift_id' => $data['shift_id'],
                'date' => Carbon::today('Asia/Ho_Chi_Minh'),
                'status' => 'CHECKED_IN',
                'check_in' => Carbon::now('Asia/Ho_Chi_Minh'),
                'created_at' => Carbon::now('Asia/Ho_Chi_Minh'),
            ]);

            $session = CashRegisterSession::create([
                'user_id' => $user->id,
                'user_shift_id' => $userShift->id,
                'opening_amount' => $data['opening_amount'],
                'notes' => $data['notes'],
                'opened_at' => Carbon::now('Asia/Ho_Chi_Minh'),
            ]);

            $activeShift = [
                'shift_id' => $workShift->id,
                'shift_name' => $workShift->name,
                'shift_time' => [
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ],
                'opened_at' => $session->opened_at ? $session->opened_at->setTimezone('Asia/Ho_Chi_Minh')->toISOString() : null,
            ];

            return response()->json([
                'success' => 'Ca làm việc đã được mở thành công!',
                'activeShift' => $activeShift,
                'hasActiveSession' => true,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Lỗi xác thực trong startSession: ', [
                'errors' => $e->errors(),
                'user_id' => Auth::id()
            ]);
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Lỗi trong startSession: ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            return response()->json(['errors' => ['server' => 'Có lỗi khi mở ca làm việc.']], 500);
        }
    }

    /**
     * Đóng phiên làm việc hiện tại.
     */
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

            $userShift = \App\Models\UserShift::with('workShift')->find($session->user_shift_id);
            $shiftCheck = $this->checkShiftExpiration($userShift);
            if ($shiftCheck['isExpired']) {
                Log::info('Đóng ca làm việc đã hết hạn', [
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
            ]);

            if ($userShift) {
                $userShift->update([
                    'status' => 'COMPLETED',
                    'check_out' => Carbon::now('Asia/Ho_Chi_Minh'),
                ]);
            }

            return response()->json([
                'success' => 'Ca làm việc đã được đóng thành công!',
                'hasActiveSession' => false,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Lỗi xác thực trong closeSession: ', [
                'errors' => $e->errors(),
                'user_id' => Auth::id()
            ]);
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Lỗi trong closeSession: ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            return response()->json(['errors' => ['server' => 'Có lỗi khi đóng ca làm việc.']], 500);
        }
    }

    /**
     * Tạo báo cáo ca làm việc.
     */
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

            $userShift = \App\Models\UserShift::with('workShift')->find($session->user_shift_id);
            if (!$userShift || !$userShift->workShift) {
                Log::warning('Không tìm thấy UserShift hoặc WorkShift cho báo cáo ca.', [
                    'session_id' => $session->id,
                    'user_shift_id' => $session->user_shift_id,
                    'user_id' => $user->id,
                ]);
                return response()->json(['errors' => ['server' => 'Không tìm thấy thông tin ca làm việc.']], 422);
            }

            $shiftCheck = $this->checkShiftExpiration($userShift);
            if ($shiftCheck['isExpired']) {
                Log::warning('Yêu cầu báo cáo ca khi ca đã hết hạn', [
                    'shift_id' => $userShift->workShift->id,
                    'shift_name' => $userShift->workShift->name,
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

            $customerData = $bills->groupBy('customer_id')->map(function ($group) {
                $customer = $group->first()->customer;
                return [
                    'customer_id' => $group->first()->customer_id,
                    'customer_name' => $customer ? $customer->customer_name : 'Khách lẻ',
                    'total_amount' => $group->sum('total_amount'),
                    'bill_count' => $group->count(),
                ];
            })->values();

            $report = [
                'total_sales' => $bills->sum('total_amount'),
                'total_cash' => $bills->where('payment_method', 'cash')->sum('total_amount'),
                'total_card' => $bills->where('payment_method', 'credit_card')->sum('total_amount'),
                'total_transfer' => $bills->where('payment_method', 'bank_transfer')->sum('total_amount'),
                'total_wallet' => $bills->where('payment_method', 'wallet')->sum('total_amount'),
                'bill_count' => $bills->count(),
                'customers' => $customerData,
            ];

            $shiftName = $userShift->workShift->name;
            $shiftTime = [
                'start_time' => Carbon::parse($userShift->workShift->start_time, 'Asia/Ho_Chi_Minh')->format('H:i:s'),
                'end_time' => Carbon::parse($userShift->workShift->end_time, 'Asia/Ho_Chi_Minh')->format('H:i:s'),
            ];

            $sessionData = [
                'shift_name' => $shiftName,
                'shift_time' => $shiftTime,
                'opened_at' => $session->opened_at ? $session->opened_at->setTimezone('Asia/Ho_Chi_Minh')->toISOString() : null,
                'opening_amount' => $session->opening_amount,
                'closing_amount' => $session->closing_amount,
                'actual_amount' => $session->actual_amount,
                'difference' => $session->difference ?? 0,
                'closed_at' => $session->closed_at ? $session->closed_at->setTimezone('Asia/Ho_Chi_Minh')->toISOString() : null,
                'notes' => $session->notes ?? 'Không có',
            ];

            return response()->json([
                'hasActiveSession' => true,
                'session' => $sessionData,
                'report' => $report,
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi trong shiftReport: ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            return response()->json(['errors' => ['server' => 'Có lỗi khi tạo báo cáo ca.']], 500);
        }
    }

    /**
     * Tạo báo cáo ca làm việc và cập nhật thông tin phiên.
     */
    public function generateShiftReport()
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
                return response()->json(['errors' => ['server' => 'Không có ca làm việc đang mở.']], 422);
            }

            $userShift = \App\Models\UserShift::with('workShift')->find($session->user_shift_id);
            if (!$userShift || !$userShift->workShift) {
                Log::warning('Không tìm thấy UserShift hoặc WorkShift khi tạo báo cáo.', [
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
            $difference = ($session->closing_amount ?? 0) - ($session->opening_amount + $actual_amount);

            $session->update([
                'actual_amount' => $actual_amount,
                'difference' => $difference,
            ]);

            return response()->json(['message' => 'Báo cáo ca đã được tạo thành công!']);
        } catch (\Exception $e) {
            Log::error('Lỗi trong generateShiftReport: ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            return response()->json(['errors' => ['server' => 'Có lỗi khi tạo báo cáo ca.']], 500);
        }
    }

    /**
     * Kiểm tra lô hàng hợp lệ cho sản phẩm.
     */
    public function checkBatch(Request $request, $productId)
    {
        try {
            $quantity = $request->query('quantity', 1);
            $batchItem = BatchItem::where('product_id', $productId)
                ->where('inventory_status', 'active')
                ->where('current_quantity', '>=', $quantity)
                ->whereHas('batch', function ($query) {
                    $query->whereNull('deleted_at')
                          ->where('receipt_status', 'completed');
                })
                ->where(function ($query) {
                    $query->whereNull('expiry_date')
                          ->orWhere('expiry_date', '>=', Carbon::today('Asia/Ho_Chi_Minh'));
                })
                ->orderBy('created_at', 'asc')
                ->first();

            $availableStock = $this->getAvailableStock($productId);

            if (!$batchItem) {
                return response()->json([
                    'hasValidBatch' => false,
                    'availableStock' => $availableStock,
                    'message' => 'Không có lô hàng hợp lệ hoặc đủ số lượng cho sản phẩm này.'
                ], 200);
            }

            $batch = \App\Models\Batch::where('id', $batchItem->batch_id)
                ->whereNull('deleted_at')
                ->where('receipt_status', 'completed')
                ->first();

            if (!$batch) {
                return response()->json([
                    'hasValidBatch' => false,
                    'availableStock' => $availableStock,
                    'message' => 'Lô hàng không hợp lệ hoặc đã bị xóa.'
                ], 200);
            }

            return response()->json([
                'hasValidBatch' => true,
                'batch_id' => $batchItem->batch_id,
                'availableStock' => $availableStock
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi trong checkBatch: ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'product_id' => $productId,
                'user_id' => Auth::id()
            ]);
            return response()->json(['errors' => ['server' => 'Có lỗi khi kiểm tra lô hàng.']], 500);
        }
    }

    /**
     * Xử lý giao dịch bán hàng.
     */
    public function submitSale(Request $request)
    {
        try {
            $user = Auth::user();
            if ($user->role_id !== 3) {
                Log::warning('Không có quyền truy cập.', ['user_id' => $user->id]);
                return response()->json(['errors' => ['server' => 'Không có quyền truy cập.']], 403);
            }

            Log::info('Dữ liệu submitSale: ', ['data' => $request->all(), 'user_id' => $user->id]);

            $data = $request->validate([
                'cart' => 'required|array|min:1',
                'cart.*.id' => 'required|integer|exists:products,id',
                'cart.*.quantity' => 'required|integer|min:1',
                'customer_id' => 'nullable|integer|exists:customers,id,deleted_at,NULL',
                'paymentMethod' => 'required|in:cash,bank_transfer,credit_card,wallet',
                'amountReceived' => 'required_if:paymentMethod,cash|numeric|min:0',
                'orderNotes' => 'nullable|string|max:255',
                'couponCode' => 'nullable|string|exists:promotions,coupon_code',
            ]);

            $session = CashRegisterSession::where('user_id', $user->id)
                ->whereNull('closed_at')
                ->whereNull('deleted_at')
                ->first();

            if (!$session) {
                Log::warning('Không có ca làm việc đang mở.', ['user_id' => $user->id]);
                return response()->json(['errors' => ['server' => 'Không có ca làm việc đang mở.']], 422);
            }

            $userShift = \App\Models\UserShift::with('workShift')->find($session->user_shift_id);
            if (!$userShift || !$userShift->workShift) {
                Log::warning('Không tìm thấy UserShift hoặc WorkShift.', [
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
                $cartItems = [];
                $promotion = null;
                $updatedProducts = [];

                // Validate promotion if provided
                if (!empty($data['couponCode'])) {
                    $promotion = Promotion::where('coupon_code', $data['couponCode'])
                        ->where('is_active', 1)
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

                // Validate total stock for each product
                foreach ($data['cart'] as $item) {
                    $product = Product::find($item['id']);
                    if (!$product) {
                        Log::warning('Sản phẩm không tồn tại.', ['product_id' => $item['id']]);
                        return response()->json(['errors' => ['cart' => "Sản phẩm ID {$item['id']} không tồn tại."]], 422);
                    }

                    $totalAvailableQuantity = $this->getAvailableStock($item['id']);
                    if ($totalAvailableQuantity < $item['quantity']) {
                        Log::warning('Sản phẩm không đủ tồn kho.', [
                            'product_id' => $item['id'],
                            'requested_quantity' => $item['quantity'],
                            'available_quantity' => $totalAvailableQuantity,
                        ]);
                        return response()->json(['errors' => ['cart' => "Sản phẩm {$product->name} không đủ tồn kho. Chỉ còn {$totalAvailableQuantity}."]], 422);
                    }

                    $subTotal += $product->selling_price * $item['quantity'];
                    $cartItems[] = [
                        'product_id' => $item['id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->selling_price,
                        'subtotal' => $product->selling_price * $item['quantity'],
                    ];
                }

                // Apply promotion discount
                if ($promotion) {
                    if ($promotion->type_id == 2) { // PERCENTAGE_DISCOUNT
                        $discountAmount = ($promotion->discount_value / 100) * $subTotal;
                    } elseif ($promotion->type_id == 1) { // DIRECT_DISCOUNT
                        $discountAmount = $promotion->discount_value;
                    } elseif ($promotion->type_id == 4 && $subTotal >= $promotion->min_order_value) { // ORDER_DISCOUNT
                        $discountAmount = $promotion->discount_value;
                    }
                    // Handle BUY_X_GET_Y if applicable (requires additional logic)
                }

                $totalAmount = $subTotal - $discountAmount;

                // Validate payment
                if ($data['paymentMethod'] === 'wallet' && $data['customer_id']) {
                    $customer = Customer::find($data['customer_id']);
                    if (!$customer || $customer->wallet < $totalAmount) {
                        Log::warning('Số dư ví không đủ.', ['customer_id' => $data['customer_id'], 'wallet' => $customer->wallet ?? 0]);
                        return response()->json(['errors' => ['payment' => 'Số dư ví không đủ để thanh toán.']], 422);
                    }
                } elseif ($data['paymentMethod'] === 'cash' && $data['amountReceived'] < $totalAmount) {
                    Log::warning('Số tiền nhận không đủ.', ['amount_received' => $data['amountReceived'], 'total_amount' => $totalAmount]);
                    return response()->json(['errors' => ['payment' => 'Số tiền nhận không đủ để thanh toán.']], 422);
                }

                // Create bill
                $bill = Bill::create([
                    'bill_number' => 'BILL-' . date('YmdHis') . '-' . rand(1000, 9999),
                    'customer_id' => $data['customer_id'],
                    'sub_total' => $subTotal,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'received_money' => $data['paymentMethod'] === 'cash' ? $data['amountReceived'] : $totalAmount,
                    'change_money' => $data['paymentMethod'] === 'cash' ? ($data['amountReceived'] - $totalAmount) : 0,
                    'payment_method' => $data['paymentMethod'],
                    'payment_status_id' => $data['paymentMethod'] === 'wallet' ? 2 : 1, // PAID for wallet, UNPAID for others
                    'notes' => $data['orderNotes'],
                    'cashier_id' => $user->id,
                    'session_id' => $session->id,
                    'created_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                    'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                ]);

                // Process each cart item and distribute quantities across batches
                foreach ($data['cart'] as $item) {
                    $product = Product::find($item['id']);
                    $remainingQuantity = $item['quantity'];

                    $batchItems = BatchItem::where('product_id', $item['id'])
                        ->where('inventory_status', 'active')
                        ->whereHas('batch', function ($query) {
                            $query->whereNull('deleted_at')
                                  ->where('receipt_status', 'completed');
                        })
                        ->where(function ($query) {
                            $query->whereNull('expiry_date')
                                  ->orWhere('expiry_date', '>=', Carbon::today('Asia/Ho_Chi_Minh'));
                        })
                        ->orderBy('created_at', 'asc')
                        ->get();

                    foreach ($batchItems as $batchItem) {
                        if ($remainingQuantity <= 0) {
                            break;
                        }

                        $quantityToDeduct = min($batchItem->current_quantity, $remainingQuantity);
                        if ($quantityToDeduct > 0) {
                            $newQuantity = $batchItem->current_quantity - $quantityToDeduct;
                            $batchItem->update([
                                'current_quantity' => $newQuantity,
                                'inventory_status' => $newQuantity <= 0 ? 'out_of_stock' : ($newQuantity <= ($batchItem->min_stock_level ?? 0) ? 'low_stock' : 'active'),
                                'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                            ]);

                            // Update product stock
                            $product->stock_quantity = $this->getAvailableStock($product->id);
                            $product->last_sold_at = Carbon::today('Asia/Ho_Chi_Minh');
                            $product->save();

                            // Record inventory transaction
                            InventoryTransaction::create([
                                'transaction_type_id' => 2, // EXPORT
                                'quantity_change' => -$quantityToDeduct,
                                'unit_price' => $product->selling_price,
                                'total_value' => $quantityToDeduct * $product->selling_price,
                                'transaction_date' => Carbon::now('Asia/Ho_Chi_Minh'),
                                'related_bill_id' => $bill->id,
                                'related_batch_id' => $batchItem->batch_id,
                                'user_id' => $user->id,
                                'created_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                                'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
                            ]);

                            // Create bill detail
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
                                'discount_per_item' => 0, // Adjust if promotion applies per item
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

                    // Lưu thông tin sản phẩm đã cập nhật stock
                    $updatedProducts[] = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'category' => $product->category ? $product->category->name : 'N/A',
                        'price' => $product->selling_price ?? 0,
                        'stock' => $this->getAvailableStock($product->id),
                        'image' => $product->image_url ?? '/storage/default-product.png',
                        'sku' => $product->sku,
                        'barcode' => $product->barcode,
                    ];
                }

                // Update customer wallet if applicable
                if ($data['customer_id']) {
                    $customer = Customer::find($data['customer_id']);
                    $walletBonus = $totalAmount * 0.01; // 1% of total_amount
                    $customer->wallet += $walletBonus;
                    if ($data['paymentMethod'] === 'wallet') {
                        $customer->wallet -= $totalAmount;
                    }
                    $customer->save();

                    Log::info('Cập nhật ví khách hàng.', [
                        'customer_id' => $customer->id,
                        'wallet_bonus' => $walletBonus,
                        'new_wallet_balance' => $customer->wallet,
                    ]);
                }

                // Update promotion usage if applicable
                if ($promotion) {
                    $promotion->increment('usage_count');
                    if ($data['customer_id']) {
                        // Track usage per customer if needed
                    }
                }

                // Update session amount
                $session->actual_amount = ($session->actual_amount ?? 0) + $totalAmount;
                $session->save();

                Log::info('Giao dịch bán hàng thành công.', [
                    'bill_id' => $bill->id,
                    'total_amount' => $totalAmount,
                    'user_id' => $user->id,
                ]);

                return response()->json([
                    'message' => 'Giao dịch bán hàng thành công.',
                    'bill' => [
                        'id' => $bill->id,
                        'bill_number' => $bill->bill_number,
                        'total_amount' => $bill->total_amount,
                        'created_at' => $bill->created_at,
                    ],
                    'products' => $updatedProducts,
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Lỗi khi xử lý giao dịch bán hàng.', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null,
            ]);
            return response()->json(['errors' => ['server' => 'Có lỗi xảy ra khi xử lý giao dịch. Vui lòng thử lại.']], 500);
        }
    }

    /**
     * Tạo khách hàng mới.
     */
    public function createCustomer(Request $request)
    {
        try {
            $data = $request->validate([
                'customer_name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'nullable|email|max:255',
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

            $customers = $this->getCustomers();

            return response()->json([
                'success' => 'Khách hàng đã được thêm thành công!',
                'newCustomer' => $newCustomer,
                'customers' => $customers,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Lỗi xác thực trong createCustomer: ', [
                'errors' => $e->errors(),
                'user_id' => Auth::id()
            ]);
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Lỗi trong createCustomer: ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            return response()->json(['errors' => ['server' => 'Có lỗi khi thêm khách hàng.']], 500);
        }
    }
}