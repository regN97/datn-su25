<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BillController extends Controller
{
public function index(Request $request)
{
    $query = Bill::with(['customer', 'cashier', 'paymentStatus']);

    if ($request->has('search') && $request->input('search')) {
        $query->where('bill_number', 'like', '%' . $request->input('search') . '%');
    }

    if ($request->has('filter_date') && $request->input('filter_date')) {
        $filter = $request->input('filter_date');

        switch ($filter) {
            case 'today':
                $query->whereDate('created_at', now()->toDateString());
                break;
            case 'this_week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'this_month':
                $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                break;
            case 'custom_date':
                $selectedDate = $request->input('selected_date');
                if ($selectedDate) {
                    $query->whereDate('created_at', $selectedDate);
                }
                break;
        }
    }

    $bills = $query->orderBy('created_at', 'desc')->get();

    $bills->each(function($bill) {
        if (!$bill->customer) {
            $bill->customer = (object)['customer_name' => 'Khách lẻ'];
        }
        // Thêm dòng này để tạo URL công khai cho ảnh minh chứng
        $bill->payment_proof_url = $bill->payment_proof_url ? Storage::url($bill->payment_proof_url) : null;
    });

    return Inertia::render('admin/bills/Index', [
        'bills' => $bills,
        'filters' => $request->only(['search', 'filter_date', 'selected_date']),
    ]);
}
    public function show(Bill $bill)
    {
        // Eager loading các relationships cần thiết để hiển thị chi tiết
        $bill->load(['customer', 'cashier', 'paymentStatus', 'details.product']);

        return Inertia::render('admin/bills/Show', [
            'bill' => $bill,
        ]);
    }
}
