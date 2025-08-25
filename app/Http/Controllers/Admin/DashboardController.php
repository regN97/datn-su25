<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Bill;
use Inertia\Inertia;
use App\Models\Batch;
use App\Models\Product;
use App\Models\BatchItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function salesDashboard(Request $request)
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->subDays(6)->startOfDay();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        // Doanh thu tổng (PAID - return)
        $totalRevenueBills = Bill::whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('paymentStatus', fn($q) => $q->where('code', 'PAID'))
            ->sum('total_amount');

        $totalBills = Bill::whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('paymentStatus', fn($q) => $q->where('code', 'PAID'))
            ->count();

        $totalReturned = DB::table('return_bills')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount_returned');

        $totalRevenue = $totalRevenueBills - $totalReturned;

        // Doanh thu theo ngày (PAID - return)
        $rawRevenue = Bill::selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('paymentStatus', fn($q) => $q->where('code', 'PAID'))
            ->groupBy('date')
            ->pluck('total', 'date');

        $rawReturn = DB::table('return_bills')
            ->selectRaw('DATE(created_at) as date, SUM(total_amount_returned) as total')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->pluck('total', 'date');

        $labels = [];
        $revenueData = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateStr = $current->toDateString();
            $bills = $rawRevenue[$dateStr] ?? 0;
            $returns = $rawReturn[$dateStr] ?? 0;
            $labels[] = $dateStr;
            $revenueData[] = $bills - $returns;
            $current->addDay();
        }

        // Giá trị đơn hàng trung bình (chỉ PAID)
        $rawAvg = Bill::selectRaw('DATE(created_at) as date, AVG(total_amount) as avg_value')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('paymentStatus', fn($q) => $q->where('code', 'PAID'))
            ->groupBy('date')
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

        // Top sản phẩm bán chạy (chỉ PAID)
        $topProducts = DB::table('bill_details')
            ->join('bills', 'bills.id', '=', 'bill_details.bill_id')
            ->join('products', 'products.id', '=', 'bill_details.product_id')
            ->join('order_payment_statuses as ps', 'ps.id', '=', 'bills.payment_status_id')
            ->whereBetween('bills.created_at', [$startDate, $endDate])
            ->where('ps.code', 'PAID')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('SUM(bill_details.quantity) as total_sold'),
                DB::raw('SUM(bill_details.quantity * bill_details.unit_price) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_sold')
            ->take(10)
            ->get()
            ->map(function ($p) {
                $p->total_sold = (int) $p->total_sold;
                $p->total_revenue = (int) $p->total_revenue;
                return $p;
            });

        // Số lượng đơn hàng theo ngày (chỉ PAID)
        $billCounts = Bill::selectRaw('DATE(created_at) as date, COUNT(*) as total_bills')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('paymentStatus', fn($q) => $q->where('code', 'PAID'))
            ->groupBy('date')
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

        // Chi tiêu khách hàng (cũ / mới) — chỉ PAID
        $spendingLabels = [];
        $spendingMultiData = [];
        $spendingOneData = [];
        $oldCustomers = [];

        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateStr = $current->toDateString();

            $billsInDay = Bill::whereDate('created_at', $dateStr)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereHas('paymentStatus', fn($q) => $q->where('code', 'PAID'))
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
        // Tổng giá trị tồn kho
        $total_inventory_value = BatchItem::where('inventory_status', 'active')
            ->sum(DB::raw('purchase_price * current_quantity'));
        // Số sản phẩm sắp hết hạn (hạn dùng < 30 ngày, còn tồn kho)
        $expiring_products = BatchItem::where('inventory_status', 'expiring_soon')
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
        // Top 10 sản phẩm bán chậm (theo số lượng bán ra trong 30 ngày gần nhất)
        $slowMovingProducts = DB::table('products')
            ->leftJoin('bill_details', function ($join) {
                $join->on('products.id', '=', 'bill_details.product_id')
                    ->where('bill_details.created_at', '>=', now()->subDays(30)->startOfMonth());
            })
            ->select(
                'products.id',
                'products.name',
                DB::raw('COALESCE(SUM(bill_details.quantity), 0) as sold')
            )
            ->where('products.is_active', true) // Sửa lại điều kiện theo đúng tên trường
            ->where('products.is_trackable', true) // Chỉ lấy sản phẩm có quản lý tồn kho
            ->where('products.deleted_at', null) // Không lấy sản phẩm đã xóa
            ->groupBy('products.id', 'products.name')
            ->orderBy('sold', 'asc')
            ->limit(10)
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

        // Thống kê batch items sắp hết hạn (30 ngày tới)
        $expiringBatchItems = BatchItem::with(['product', 'batch'])
            ->where('inventory_status', 'expiring_soon')
            ->where('current_quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays(30))
            ->select([
                'batch_items.id',
                'batch_items.product_id',
                'batch_items.batch_id', // Thêm batch_id
                'batch_items.current_quantity',
                'batch_items.expiry_date',
                DB::raw('DATEDIFF(expiry_date, CURDATE()) as days_until_expiry')
            ])
            ->orderBy('expiry_date')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product->name,
                    'batch_number' => $item->batch->batch_number, // Thêm batch_number
                    'current_quantity' => $item->current_quantity,
                    'expiry_date' => $item->expiry_date->format('d/m/Y'),
                    'days_until_expiry' => $item->days_until_expiry,
                ];
            });

        // Thêm logic thống kê sản phẩm sắp hết hàng
        $lowStockProducts = Product::where('is_active', true)
            ->where('is_trackable', true)
            ->whereRaw('stock_quantity <= min_stock_level')
            ->where('stock_quantity', '>', 0) // Chỉ lấy sp còn hàng
            ->select([
                'id',
                'name',
                'sku',
                'stock_quantity',
                'min_stock_level',
                'selling_price'
            ])
            ->orderBy('stock_quantity')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'current_stock' => $product->stock_quantity,
                    'min_stock' => $product->min_stock_level,
                    'remaining_percent' => round(($product->stock_quantity / $product->min_stock_level) * 100)
                ];
            });

        // Thêm số lượng sản phẩm sắp hết vào overviewStats
        $low_stock_count = $lowStockProducts->count();

        // Thống kê sản phẩm hết hàng
        $outOfStockProducts = Product::where('is_active', true)
            ->where('is_trackable', true)
            ->where('stock_quantity', '=', 0)
            ->select([
                'id',
                'name',
                'sku',
                'min_stock_level',
                'selling_price'
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'min_stock' => $product->min_stock_level
                ];
            });

        // Truyền sang view
        return Inertia::render('admin/dashboard/InventoryDashboard', [
            'overviewStats' => [
                ['label' => 'Tổng sản phẩm', 'value' => $total_products, 'icon' => 'Package', 'color' => 'text-blue-500'],
                ['label' => 'Tổng giá trị tồn kho trong 1 năm', 'value' => $total_inventory_value, 'icon' => 'HandCoins', 'color' => 'text-green-500', 'unit' => 'VND'],
                ['label' => 'Lô sắp hết hạn trong 30 ngày', 'value' => $expiring_products, 'icon' => 'AlertTriangle', 'color' => 'text-red-500'],
                ['label' => 'Sản phẩm sắp hết hàng', 'value' => $low_stock_count, 'icon' => 'AlertTriangle', 'color' => 'text-red-500'],
            ],
            'chartData' => $chartData,
            'products' => $products,
            'slowMovingProducts' => $slowMovingProducts,
            'returnStats' => $returnStats,
            'expiringBatchItems' => $expiringBatchItems,
            'lowStockProducts' => $lowStockProducts,
            'outOfStockProducts' => $outOfStockProducts
        ]);
    }
}
