<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\BatchItem;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    public function index()
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
                'value' => (float)($rawChartData[$month] ?? 0),
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
        return Inertia::render('Test', [
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
