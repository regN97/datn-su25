<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnBill;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class ReturnBillController extends Controller
{
    /**
     * Hiển thị trang danh sách đơn trả hàng cho Admin.
     * Xử lý tìm kiếm và lọc ngay tại đây.
     */
    public function index(Request $request)
    {
        // Khởi tạo truy vấn ban đầu với các mối quan hệ cần thiết
        $query = ReturnBill::query()
            ->with([
                'bill:id,bill_number',
                'cashier:id,name',
                'details.product:id,name'
            ])
            ->latest();

        // Lấy các bộ lọc từ request
        $filters = $request->only(['search', 'filter_date', 'selected_date']);

        // Áp dụng bộ lọc tìm kiếm
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('return_bill_number', 'like', $searchTerm)
                  ->orWhereHas('cashier', function ($q) use ($searchTerm) {
                      $q->where('name', 'like', $searchTerm);
                  })
                  ->orWhereHas('bill', function ($q) use ($searchTerm) {
                      $q->where('bill_number', 'like', $searchTerm);
                  });
            });
        }

        // Áp dụng bộ lọc theo ngày
        if ($request->filled('filter_date')) {
            $filterDate = $request->input('filter_date');
            
            switch ($filterDate) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'this_week':
                    $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', Carbon::now()->month);
                    break;
                case 'custom_date':
                    if ($request->filled('selected_date')) {
                        $query->whereDate('created_at', $request->input('selected_date'));
                    }
                    break;
            }
        }
        
        // Lấy dữ liệu đã lọc
        $returnBills = $query->get();

        // Trả về trang Inertia với dữ liệu và các bộ lọc hiện tại
        return Inertia::render('admin/return_bills/Index', [
            'returnBills' => $returnBills,
            'filters' => $filters,
        ]);
    }
}
