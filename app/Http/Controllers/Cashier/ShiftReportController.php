<?php

namespace App\Http\Controllers\Cashier;

use Carbon\Carbon;
use App\Models\Bill;
use App\Models\User;
use Inertia\Inertia;
use App\Models\UserShift;
use App\Models\BillDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ShiftReportController extends Controller
{
    public function showReport(Request $request)
    {
        try {
            $user = Auth::user();

            // Lấy ca làm việc hiện tại
            $currentUserShift = UserShift::where('user_id', $user->id)
                ->where('status', 'CHECKED_IN')
                ->latest()
                ->first();

            if (!$currentUserShift) {
                return Inertia::render('cashier/pos/ShiftReport', [
                    'shiftReport' => null,
                    'error' => 'Không tìm thấy ca làm việc đang mở.'
                ]);
            }

            $endTime = $currentUserShift->check_out ? Carbon::parse($currentUserShift->check_out) : now();

            $bills = Bill::where('cashier_id', $user->id)
                ->whereBetween('created_at', [$currentUserShift->check_in, $endTime])
                ->with(['details', 'details.product', 'paymentStatus', 'customer'])
                ->orderBy('created_at', 'desc')
                ->get();

            $totalRevenue = $bills->where('payment_status_id', 2)->sum('total_amount');
            $cashRevenue = $bills->where('payment_status_id', 2)
                ->where('payment_method', 'cash')
                ->sum('total_amount');
            $bankRevenue = $bills->where('payment_status_id', 2)
                ->whereIn('payment_method', ['card', 'bank_transfer', 'credit_card', 'vnpay'])
                ->sum('total_amount');
            $returnValue = $bills->where('payment_status_id', 3)->sum('total_amount');
            $pendingRevenue = $bills->where('payment_status_id', 1)->sum('total_amount');
            $totalTransactions = $bills->count();
            $netRevenue = $totalRevenue - $returnValue;

            // Thống kê sản phẩm bán chạy nhất
            $topProducts = BillDetail::whereIn('bill_id', $bills->pluck('id'))
                ->groupBy('product_id')
                ->select('product_id', DB::raw('SUM(quantity) as total_quantity'))
                ->with(['product' => function ($query) {
                    $query->select('id', 'name');
                }])
                ->orderBy('total_quantity', 'desc')
                ->take(5)
                ->get()
                ->map(function ($detail) {
                    return [
                        'product_name' => $detail->product ? $detail->product->name : 'N/A',
                        'total_quantity' => $detail->total_quantity,
                    ];
                });

            // Thống kê khách hàng mua nhiều nhất
            $topCustomers = $bills->groupBy('customer_id')
                ->map(function ($group) {
                    $customer = $group->first()->customer;
                    return [
                        'customer_name' => $customer ? $customer->customer_name : 'Khách lẻ',
                        'total_amount' => $group->sum('total_amount'),
                        'bill_count' => $group->count(),
                    ];
                })
                ->sortByDesc('total_amount')
                ->take(5)
                ->values();

            $transactions = $bills->map(function ($bill) {
                $details = $bill->details->map(function ($detail) {
                    return [
                        'product_name' => $detail->product ? $detail->product->name : $detail->p_name,
                        'quantity' => $detail->quantity,
                        'unit_price' => $detail->unit_price,
                        'total' => $detail->quantity * $detail->unit_price
                    ];
                })->toArray();

                return [
                    'bill_id' => $bill->bill_number,
                    'time' => Carbon::parse($bill->created_at)->format('H:i A'),
                    'amount' => $bill->payment_status_id === 3 ? -$bill->total_amount : $bill->total_amount,
                    'payment_method' => match ($bill->payment_method) {
                        'cash' => 'Tiền mặt',
                        'card' => 'Thẻ',
                        'bank_transfer' => 'Chuyển khoản',
                        'credit_card' => 'Thẻ tín dụng',
                        'vnpay' => 'VNPay',
                        default => 'Khác',
                    },
                    'type' => $bill->payment_status_id === 3 ? 'Trả hàng' : ($bill->payment_status_id === 1 ? 'Chờ thanh toán' : 'Bán hàng'),
                    'payment_status' => $bill->paymentStatus ? $bill->paymentStatus->name : match ($bill->payment_status_id) {
                        1 => 'Chưa thanh toán',
                        2 => 'Đã thanh toán',
                        3 => 'Hoàn tiền',
                        default => 'Không xác định',
                    },
                    'details' => $details
                ];
            })->toArray();

            $duration = $currentUserShift->total_hours ? $currentUserShift->total_hours . ' giờ' : 
                Carbon::parse($currentUserShift->check_in)->diff($endTime)->format('%H giờ %I phút');

            $shiftReport = [
                'shift' => [
                    'id' => $currentUserShift->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'shift_name' => 'Ca làm việc ' . $currentUserShift->id,
                    'shift_description' => $currentUserShift->notes ?? 'Không có mô tả',
                    'date' => $currentUserShift->date,
                    'start_time' => Carbon::parse($currentUserShift->check_in)->format('Y-m-d H:i:s'),
                    'end_time' => $currentUserShift->check_out ? Carbon::parse($currentUserShift->check_out)->format('Y-m-d H:i:s') : null,
                    'duration' => $duration,
                    'notes' => $currentUserShift->notes ?? '',
                    'status' => $currentUserShift->status
                ],
                'summary' => [
                    'total_revenue' => $totalRevenue,
                    'cash_revenue' => $cashRevenue,
                    'bank_revenue' => $bankRevenue,
                    'return_value' => $returnValue,
                    'pending_revenue' => $pendingRevenue,
                    'net_revenue' => $netRevenue,
                    'total_transactions' => $totalTransactions,
                    'pending_transactions' => $bills->where('payment_status_id', 1)->count(),
                    'completed_transactions' => $bills->where('payment_status_id', 2)->count(),
                    'refunded_transactions' => $bills->where('payment_status_id', 3)->count(),
                    'top_products' => $topProducts,
                    'top_customers' => $topCustomers,
                ],
                'transactions' => $transactions
            ];

            return Inertia::render('cashier/pos/ShiftReport', [
                'shiftReport' => $shiftReport
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi trong showReport: ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            return Inertia::render('cashier/pos/ShiftReport', [
                'shiftReport' => null,
                'error' => 'Có lỗi khi tạo báo cáo ca làm việc.'
            ]);
        }
    }

    public function saveNotes(Request $request)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000'
        ]);

        $user = Auth::user();
        $currentUserShift = UserShift::where('user_id', $user->id)
            ->where('status', 'CHECKED_IN')
            ->latest()
            ->first();

        if (!$currentUserShift) {
            return response()->json(['message' => 'Không tìm thấy ca làm việc đang mở.'], 404);
        }

        $currentUserShift->update([
            'notes' => $request->notes
        ]);

        return response()->json(['message' => 'Lưu ghi chú thành công.']);
    }

    public function endShift(Request $request)
    {
        try {
            $user = Auth::user();
            $currentUserShift = UserShift::where('user_id', $user->id)
                ->where('status', 'CHECKED_IN')
                ->latest()
                ->first();

            if (!$currentUserShift) {
                Log::warning('Không tìm thấy ca làm việc đang mở.', ['user_id' => $user->id]);
                return response()->json(['message' => 'Không tìm thấy ca làm việc đang mở.'], 404);
            }

            // Validate input
            $data = $request->validate([
                'closing_amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:255',
            ]);

            // Cập nhật ca làm việc
            $currentUserShift->update([
                'check_out' => now(),
                'status' => 'COMPLETED',
                'total_hours' => Carbon::parse($currentUserShift->check_in)->diffInHours(now()),
                'notes' => $data['notes']
            ]);

            // Cập nhật cash_register_sessions
            $session = \App\Models\CashRegisterSession::where('user_id', $user->id)
                ->whereNull('closed_at')
                ->latest()
                ->first();

            if ($session) {
                $session->update([
                    'closing_amount' => $data['closing_amount'],
                    'closed_at' => now(),
                    'notes' => $data['notes'],
                    'actual_amount' => $data['closing_amount'],
                    'difference' => $data['closing_amount'] - $session->opening_amount
                ]);
            }

            return response()->json(['message' => 'Ca làm việc đã kết thúc thành công.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Lỗi xác thực trong endShift: ', [
                'errors' => $e->errors(),
                'user_id' => Auth::id()
            ]);
            return response()->json(['message' => 'Dữ liệu không hợp lệ.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Lỗi trong endShift: ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            return response()->json(['message' => 'Có lỗi khi kết thúc ca làm việc.'], 500);
        }
    }

    public function history(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);

            $userShifts = UserShift::where('user_id', $user->id)
                ->where('status', 'COMPLETED')
                ->orderBy('check_out', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            $shiftHistory = $userShifts->map(function ($userShift) {
                $bills = Bill::where('cashier_id', $userShift->user_id)
                    ->whereBetween('created_at', [$userShift->check_in, $userShift->check_out])
                    ->whereNull('deleted_at')
                    ->get();

                $totalRevenue = $bills->where('payment_status_id', 2)->sum('total_amount');
                $cashRevenue = $bills->where('payment_status_id', 2)
                    ->where('payment_method', 'cash')
                    ->sum('total_amount');
                $bankRevenue = $bills->where('payment_status_id', 2)
                    ->whereIn('payment_method', ['card', 'bank_transfer', 'credit_card', 'vnpay'])
                    ->sum('total_amount');

                return [
                    'shift_id' => $userShift->id,
                    'shift_name' => 'Ca làm việc ' . $userShift->id,
                    'date' => $userShift->date,
                    'start_time' => Carbon::parse($userShift->check_in)->format('Y-m-d H:i:s'),
                    'end_time' => $userShift->check_out ? Carbon::parse($userShift->check_out)->format('Y-m-d H:i:s') : null,
                    'duration' => $userShift->total_hours ? $userShift->total_hours . ' giờ' : 
                        Carbon::parse($userShift->check_in)->diff($userShift->check_out)->format('%H giờ %I phút'),
                    'total_revenue' => $totalRevenue,
                    'cash_revenue' => $cashRevenue,
                    'bank_revenue' => $bankRevenue,
                    'total_transactions' => $bills->count(),
                    'notes' => $userShift->notes ?? '',
                ];
            });

            return Inertia::render('cashier/pos/ShiftHistory', [
                'shiftHistory' => [
                    'data' => $shiftHistory,
                    'current_page' => $userShifts->currentPage(),
                    'last_page' => $userShifts->lastPage(),
                    'per_page' => $userShifts->perPage(),
                    'total' => $userShifts->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi trong history: ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            return Inertia::render('cashier/pos/ShiftHistory', [
                'shiftHistory' => null,
                'error' => 'Có lỗi khi lấy lịch sử ca làm việc.'
            ]);
        }
    }
}