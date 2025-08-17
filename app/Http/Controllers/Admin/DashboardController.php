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
    public function salesDashboard(Request $request)
    {
        // Lấy ngày bắt đầu và kết thúc từ request hoặc mặc định 7 ngày gần nhất
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->subDays(6)->startOfDay();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        // Tổng doanh thu trong khoảng thời gian
        $totalRevenue = Bill::whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');

        // Tổng đơn hàng
        $totalBills = Bill::whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Doanh thu theo ngày
        $rawRevenue = Bill::selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->pluck('total', 'date');

        $labels = [];
        $revenueData = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateStr = $current->toDateString();
            $labels[] = $dateStr;
            $revenueData[] = $rawRevenue[$dateStr] ?? 0;
            $current->addDay();
        }

        // Giá trị đơn hàng trung bình
        $rawAvg = Bill::selectRaw('DATE(created_at) as date, AVG(total_amount) as avg_value')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->pluck('avg_value', 'date');

        $avgLabels = [];
        $avgData = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateStr = $current->toDateString();
            $avgLabels[] = $dateStr;
            $avgData[] = isset($rawAvg[$dateStr]) ? (float) $rawAvg[$dateStr] : 0;
            $current->addDay();
        }

        // Top sản phẩm bán chạy
        $topProducts = DB::table('bill_details')
            ->join('bills', 'bills.id', '=', 'bill_details.bill_id')
            ->join('products', 'products.id', '=', 'bill_details.product_id')
            ->whereBetween('bills.created_at', [$startDate, $endDate])
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
            ->get()
            ->map(function ($p) {
                $p->total_sold = (int) $p->total_sold;
                $p->total_revenue = (int) $p->total_revenue;
                return $p;
            });

        //  Số lượng đơn hàng theo ngày
        $billCounts = Bill::selectRaw('DATE(created_at) as date, COUNT(*) as total_bills')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->pluck('total_bills', 'date');

        $billLabels = [];
        $billData = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateStr = $current->toDateString();
            $billLabels[] = $dateStr;
            $billData[] = $billCounts[$dateStr] ?? 0;
            $current->addDay();
        }

        // 👥 Chi tiêu của khách hàng (khách mới vs khách cũ)
        $spendingLabels = [];
        $spendingMultiData = [];
        $spendingOneData = [];

        $oldCustomers = [];

        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateStr = $current->toDateString();

            $billsInDay = Bill::whereDate('created_at', $dateStr)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereNotNull('customer_id')
                ->get();

            $dayNewSpent = 0;
            $dayReturningSpent = 0;

            $grouped = $billsInDay->groupBy('customer_id');

            foreach ($grouped as $customerId => $bills) {
                $totalSpent = $bills->sum('total_amount');
                if (in_array($customerId, $oldCustomers)) {
                    $dayReturningSpent += $totalSpent;
                } else {
                    $dayNewSpent += $totalSpent;
                }
            }

            $oldCustomers = array_unique(array_merge($oldCustomers, $billsInDay->pluck('customer_id')->toArray()));

            if ($dayReturningSpent > 0 || $dayNewSpent > 0) {
                $spendingLabels[] = $current->format('d/m');
                $spendingMultiData[] = $dayReturningSpent;
                $spendingOneData[] = $dayNewSpent;
            }

            $current->addDay();
        }

        // Trả dữ liệu về Vue Inertia
        return Inertia::render('admin/dashboard/SalesDashboard', [
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
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

    public function inventoryDashboard()
    {
        // Tổng số sản phẩm
        $total_products = Product::count();
        // Tổng tồn kho
        $total_stock = Product::all()->sum(fn($p) => $p->getCurrentStock());
        // Tổng giá trị tồn kho
        $total_inventory_value = BatchItem::where('inventory_status', 'active')
            ->sum(DB::raw('purchase_price * current_quantity'));
        // Số sản phẩm sắp hết hạn (hạn dùng < 30 ngày, còn tồn kho)
        $expiring_products = BatchItem::where('inventory_status', 'active')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->addDays(30))
            ->where('current_quantity', '>', 0)
            ->count();
        // Dữ liệu biểu đồ: tổng giá trị tồn kho theo tháng (12 tháng gần nhất)
        $months = collect(range(0, 11))->map(function ($i) {
            return now()->subMonths(11 - $i)->format('Y-m');
        });
        $rawChartData = DB::table('batch_items')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(purchase_price * current_quantity) as value')
            ->where('inventory_status', 'active')
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->pluck('value', 'month');
        $chartData = $months->map(function ($month) use ($rawChartData) {
            return [
                'month' => $month,
                'value' => (float) ($rawChartData[$month] ?? 0),
            ];
        });
        // Danh sách sản phẩm
        $products = Product::with(['category', 'unit'])
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'selling_price' => $product->selling_price,
                    'current_stock' => $product->getCurrentStock(),
                ];
            });
        // Top 5 sản phẩm bán chạy (theo số lượng bán ra trong 12 tháng gần nhất)
        $topSellingProducts = DB::table('bill_details')
            ->join('products', 'bill_details.product_id', '=', 'products.id')
            ->select('products.id', 'products.name', DB::raw('SUM(bill_details.quantity) as sold'))
            ->where('bill_details.created_at', '>=', now()->subMonths(12)->startOfMonth())
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('sold')
            ->limit(5)
            ->get();
        // Thống kê trả hàng
        $returnStats = [
            'totalReturns' => DB::table('purchase_returns')->count(),
            'totalReturnValue' => DB::table('purchase_returns')->sum('total_value_returned'),
            'mostReturnedProduct' => DB::table('purchase_return_items')
                ->join('products', 'purchase_return_items.product_id', '=', 'products.id')
                ->select('products.name', DB::raw('SUM(quantity_returned) as qty'))
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('qty')
                ->value('products.name'),
        ];
        // Truyền sang view
        return Inertia::render('admin/dashboard/InventoryDashboard', [
            'overviewStats' => [
                ['label' => 'Tổng sản phẩm', 'value' => $total_products, 'icon' => 'Package', 'color' => 'text-blue-500'],
                ['label' => 'Tổng tồn kho', 'value' => $total_stock, 'icon' => 'Layers', 'color' => 'text-green-500'],
                ['label' => 'Tổng giá trị tồn kho', 'value' => $total_inventory_value, 'icon' => 'TrendingUp', 'color' => 'text-indigo-500', 'unit' => 'VND'],
                ['label' => 'Sắp hết hạn', 'value' => $expiring_products, 'icon' => 'AlertTriangle', 'color' => 'text-yellow-500'],
            ],
            'chartData' => $chartData,
            'products' => $products,
            'topSellingProducts' => $topSellingProducts,
            'returnStats' => $returnStats,
        ]);
    }
}
