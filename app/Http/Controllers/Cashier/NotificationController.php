<?php

namespace App\Http\Controllers\Cashier;

use App\Models\Bill;
use Inertia\Inertia;
use App\Models\Promotion;
use App\Models\CashRegisterSession;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user instanceof \App\Models\User) {
            Log::error('Invalid user object', ['user' => $user]);
            return redirect()->route('cashier.login');
        }

        $recentActivities = $this->getRecentActivities($user->id);
        $quickStats = $this->getQuickStats();
        $importantNotifications = $this->getImportantNotifications();

        return Inertia::render('cashier/pos/Notifications', [
            'recentActivities' => $recentActivities->toArray(),
            'quickStats' => $quickStats,
            'importantNotifications' => $importantNotifications->toArray(),
            'user' => $user->only(['id', 'name', 'email']),
        ]);
    }

    private function getRecentActivities($userId)
    {
        $bills = Bill::where('cashier_id', $userId)
            ->where('created_at', '>=', now()->subDays(7))
            ->latest()
            ->take(3)
            ->get()
            ->map(function ($bill) {
                return [
                    'description' => "Thực hiện giao dịch bán hàng: Hóa đơn #{$bill->bill_number} (Tổng: " . number_format($bill->total_amount, 0, ',', '.') . " VNĐ)",
                    'time' => $bill->created_at->format('d/m/Y h:i A'),
                ];
            });

        $sessions = CashRegisterSession::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(7))
            ->latest()
            ->take(2)
            ->get()
            ->map(function ($session) {
                return [
                    'description' => $session->closed_at ? 'Đăng xuất hệ thống: Đã đăng xuất.' : 'Đăng nhập hệ thống: Đăng nhập thành công.',
                    'time' => ($session->closed_at ?? $session->opened_at)->format('d/m/Y h:i A'),
                ];
            });

        return $bills->concat($sessions)->sortByDesc('time')->take(5)->values();
    }

    private function getQuickStats()
    {
        $today = now()->startOfDay();
        $totalTransactions = Bill::where('created_at', '>=', $today)->count();
        $totalRevenue = Bill::where('created_at', '>=', $today)->sum('total_amount');
        $totalReturns = \App\Models\PurchaseReturn::where('created_at', '>=', $today)->count();
        $bestSellingProduct = \App\Models\BillDetail::where('created_at', '>=', $today)
            ->groupBy('p_name')
            ->selectRaw('p_name, SUM(quantity) as total_quantity')
            ->orderByDesc('total_quantity')
            ->first();

        return [
            'totalTransactions' => $totalTransactions,
            'totalRevenue' => number_format($totalRevenue, 0, ',', '.'),
            'totalReturns' => $totalReturns,
            'bestSellingProduct' => $bestSellingProduct ? $bestSellingProduct->p_name : 'Chưa có',
        ];
    }

    private function getImportantNotifications()
    {
        $promotions = Promotion::where('end_date', '>=', now())
            ->where('is_active', 1)
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($promotion) {
                $discount = $promotion->discount_type === 'percentage' 
                    ? "{$promotion->discount_value}%"
                    : number_format($promotion->discount_value, 0, ',', '.') . " VNĐ";
                return [
                    'message' => "Khuyến mãi \"{$promotion->name}\" đang diễn ra: Giảm {$discount} cho đơn hàng từ " . number_format($promotion->min_order_amount, 0, ',', '.') . " VNĐ. Kết thúc: {$promotion->end_date->format('d/m/Y')}.",
                    'time' => $promotion->created_at->format('d/m/Y h:i A'),
                    'isNew' => $promotion->created_at->isToday(),
                    'details' => $promotion->description ?? 'Không có mô tả chi tiết.',
                ];
            });

        $systemNotifications = collect([
            [
                'message' => 'Hệ thống bảo trì định kỳ vào 23:00 hôm nay. Vui lòng hoàn tất giao dịch trước giờ bảo trì.',
                'time' => now()->subHours(12)->format('d/m/Y h:i A'),
                'isNew' => false,
                'details' => 'Bảo trì dự kiến kéo dài 2 giờ. Hệ thống sẽ tạm ngưng xử lý giao dịch.',
            ],
            // [
            //     'message' => 'Cập nhật phần mềm POS phiên bản 2.1.3 đã hoàn tất.',
            //     'time' => now()->subHours(6)->format('d/m/Y h:i A'),
            //     'isNew' => true,
            //     'details' => 'Phiên bản mới cải thiện hiệu suất in hóa đơn và xử lý trả hàng.',
            // ],
            [
                'message' => 'Nhắc nhở: Kiểm tra tồn kho trước khi đóng ca.',
                'time' => now()->subHours(3)->format('d/m/Y h:i A'),
                'isNew' => false,
                'details' => 'Vui lòng đối chiếu số lượng sản phẩm thực tế với dữ liệu hệ thống.',
            ],
            // [
            //     'message' => 'Cảnh báo: Sản phẩm "Sữa tươi Vinamilk" sắp hết hàng.',
            //     'time' => now()->subHours(1)->format('d/m/Y h:i A'),
            //     'isNew' => true,
            //     'details' => 'Tồn kho hiện tại: 5 sản phẩm. Vui lòng liên hệ bộ phận nhập hàng.',
            // ],
        ]);

        return $promotions->concat($systemNotifications)->sortByDesc('time')->take(10)->values();
    }
}
?>