<?php

// app/Http/Controllers/Admin/StockRequestController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Redirect;

class StockRequestController extends Controller
{
    /**
     * Hiển thị danh sách tất cả các thông báo yêu cầu nhập hàng.
     */
    public function index()
    {
        // Lấy tất cả các thông báo yêu cầu nhập hàng của admin hiện tại
        $stockRequests = Auth::user()
            ->notifications()
            ->where('type', 'App\Notifications\StockReplenishmentRequest')
            ->get();

        return Inertia::render('admin/notifications/StockRequests', [
            'stockRequests' => $stockRequests,
        ]);
    }

    /**
     * Đánh dấu một thông báo là đã đọc.
     */
public function markAsRead($notificationId)
{
    try {
        $admin = Auth::user();
        $notification = $admin->notifications()->findOrFail($notificationId);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        // Lấy lại danh sách notifications sau khi cập nhật
        $stockRequests = Auth::user()
            ->notifications()
            ->where('type', 'App\Notifications\StockReplenishmentRequest')
            ->get();

        return Redirect::back()
            ->with('success', 'Thông báo đã được đánh dấu là đã đọc.')
            ->with('stockRequests', $stockRequests);
    } catch (\Exception $e) {
        return Redirect::back()
            ->with('error', 'Có lỗi xảy ra khi đánh dấu đã đọc: ' . $e->getMessage());
    }
}

    /**
     * Xóa một thông báo.
     */
public function delete($notificationId)
{
    try {
        $admin = Auth::user();
        $notification = $admin->notifications()->findOrFail($notificationId);
        $notification->delete();

        // Lấy lại danh sách notifications sau khi xóa
        $stockRequests = Auth::user()
            ->notifications()
            ->where('type', 'App\Notifications\StockReplenishmentRequest')
            ->get();

        // Trả về Inertia response với dữ liệu mới
        return Redirect::back()
            ->with('success', 'Yêu cầu đã được xóa thành công.')
            ->with('stockRequests', $stockRequests);
    } catch (\Exception $e) {
        return Redirect::back()
            ->with('error', 'Có lỗi xảy ra khi xóa yêu cầu: ' . $e->getMessage());
    }
}

    /**
     * Lấy số lượng thông báo chưa đọc (dành cho API hoặc Inertia prop).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnreadNotificationsCount()
    {
        $unreadCount = Auth::user()->unreadNotifications()
            ->where('type', 'App\Notifications\StockReplenishmentRequest')
            ->count();

        return response()->json(['count' => $unreadCount]);
    }
}
