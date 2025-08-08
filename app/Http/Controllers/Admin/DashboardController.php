<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Bill;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\BatchItem;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\BillDetail;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Tính mốc bắt đầu 30 ngày trước
        $startDate = now()->subDays(30);
        $endDate = now();

        // Tổng doanh thu trong 30 ngày
        $totalRevenue = Bill::whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');

        // Tổng đơn hàng trong 30 ngày
        $totalBills = Bill::whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Doanh thu theo ngày trong 30 ngày
        $rawRevenue = Bill::selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->pluck('total', 'date');

        $labels = [];
        $revenueData = [];
        $current = $startDate->copy();
        while ($current <= now()) {
            $dateStr = $current->toDateString();
            $labels[] = $dateStr;
            $revenueData[] = $rawRevenue[$dateStr] ?? 0;
            $current->addDay();
        }

        // Giá trị đơn hàng trung bình theo ngày trong 30 ngày
        $rawAvg = Bill::selectRaw('DATE(created_at) as date, AVG(total_amount) as avg_value')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->pluck('avg_value', 'date');

        $avgLabels = [];
        $avgData = [];
        $current = $startDate->copy();
        while ($current <= now()) {
            $dateStr = $current->toDateString();
            $avgLabels[] = $dateStr;
            $avgData[] = isset($rawAvg[$dateStr]) ? (float) $rawAvg[$dateStr] : 0;
            $current->addDay();
        }

        // Top sản phẩm bán chạy trong 30 ngày
        $topProducts = DB::table('bill_details')
            ->join('bills', 'bills.id', '=', 'bill_details.bill_id')
            ->join('products', 'products.id', '=', 'bill_details.product_id')
            ->where('bills.created_at', '>=', $startDate)
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('SUM(bill_details.quantity) as total_sold'),
                DB::raw('SUM(bill_details.quantity * products.selling_price) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.selling_price')
            ->orderByDesc('total_sold')
            ->take(10)
            ->get();

        $topProducts = $topProducts->map(function ($p) {
            $p->total_sold = (int) $p->total_sold;
            $p->total_revenue = (int) $p->total_revenue;
            return $p;
        });

        // Số lượng đơn hàng theo ngày trong 30 ngày
        $billCounts = Bill::selectRaw('DATE(created_at) as date, COUNT(*) as total_bills')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->pluck('total_bills', 'date');

        $billLabels = [];
        $billData = [];
        $current = $startDate->copy();
        while ($current <= now()) {
            $dateStr = $current->toDateString();
            $billLabels[] = $dateStr;
            $billData[] = $billCounts[$dateStr] ?? 0;
            $current->addDay();
        }

        // Chi tiêu khách hàng theo ngày trong 30 ngày
        $startDate = now()->subDays(30);
        $endDate = now();

        $spendingLabels = [];
        $spendingMultiData = []; // khách cũ
        $spendingOneData = [];   // khách mới

        $oldCustomers = []; // khách đã mua trước đó

        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateStr = $current->toDateString();

            // Lấy tất cả bill trong ngày này
            $billsInDay = Bill::whereDate('created_at', $dateStr)
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('customer_id')
                ->get();

            $dayNewSpent = 0;
            $dayReturningSpent = 0;

            // Gom nhóm theo khách hàng
            $grouped = $billsInDay->groupBy('customer_id');
            foreach ($grouped as $customerId => $bills) {
                $totalSpent = $bills->sum('total_amount');
                if (in_array($customerId, $oldCustomers)) {
                    $dayReturningSpent += $totalSpent;
                } else {
                    $dayNewSpent += $totalSpent;
                }
            }

            // Cập nhật danh sách khách cũ
            $oldCustomers = array_unique(array_merge($oldCustomers, $billsInDay->pluck('customer_id')->toArray()));

            // 👉 Chỉ push vào mảng nếu có dữ liệu
            if ($dayReturningSpent > 0 || $dayNewSpent > 0) {
                $spendingLabels[] = $current->format('d/m');
                $spendingMultiData[] = $dayReturningSpent;
                $spendingOneData[] = $dayNewSpent;
            }

            $current->addDay();
        }

        // Trả dữ liệu về Inertia
        return Inertia::render('Dashboard', [
            'dashboards' => [
                'totalRevenue' => (int) $totalRevenue,
                'totalBills' => $totalBills,
                'labels' => $labels,
                'revenueData' => $revenueData,
                'avgLabels' => $avgLabels,
                'avgData' => $avgData,
                'topProducts' => $topProducts,
                'billLabels' => $billLabels,
                'billData' => $billData,
                'customerSpending' => [
                    'labels' => $spendingLabels,
                    'multiData' => $spendingMultiData,
                    'oneData' => $spendingOneData,
                ],
            ],
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
