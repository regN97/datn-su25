<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\PurchaseReturn;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Tổng quan
        $overview = [
            'total_products' => Product::count(),
            'pending_orders' => PurchaseOrder::whereHas('status', function($q) {
                $q->where('name', 'pending');
            })->count(),
            'total_suppliers' => Supplier::count(),
            'total_users' => User::count(),
        ];

        // Biểu đồ nhập hàng theo tháng (12 tháng gần nhất)
        $months = collect(range(0, 11))->map(function ($i) {
            return Carbon::now()->subMonths(11 - $i)->format('Y-m');
        });
        $importsRaw = Batch::select(DB::raw('DATE_FORMAT(received_date, "%Y-%m") as month'), DB::raw('SUM(total_amount) as value'))
            ->where('received_date', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');
        $imports = $months->map(function ($month) use ($importsRaw) {
            return [
                'month' => $month,
                'value' => isset($importsRaw[$month]) ? (int) $importsRaw[$month]->value : 0,
            ];
        });

        // Biểu đồ trả hàng theo tháng (12 tháng gần nhất)
        $returnsRaw = PurchaseReturn::select(DB::raw('DATE_FORMAT(return_date, "%Y-%m") as month'), DB::raw('SUM(total_value_returned) as value'))
            ->where('return_date', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');
        $returns = $months->map(function ($month) use ($returnsRaw) {
            return [
                'month' => $month,
                'value' => isset($returnsRaw[$month]) ? (int) $returnsRaw[$month]->value : 0,
            ];
        });

        // Tồn kho hiện tại (theo sản phẩm)
        $inventory = Product::with('category')->get()->map(function($product) {
            $stock = BatchItem::where('product_id', $product->id)->sum('current_quantity');
            return [
                'name' => $product->name,
                'stock' => $stock,
                'category' => optional($product->category)->name,
            ];
        });

        // Sản phẩm bán chạy (top nhập nhiều nhất)
        $topProducts = BatchItem::select('product_id', DB::raw('SUM(received_quantity) as total_imported'))
            ->groupBy('product_id')
            ->orderByDesc('total_imported')
            ->take(5)
            ->get()
            ->map(function($item) {
                $product = Product::find($item->product_id);
                return [
                    'name' => $product ? $product->name : 'N/A',
                    'total_imported' => (int) $item->total_imported,
                ];
            });

        // Hoạt động gần đây (nhập hàng, trả hàng)
        $recentImports = Batch::latest('received_date')->take(5)->get()->map(function($batch) {
            return [
                'type' => 'import',
                'message' => 'Nhập kho: ' . optional($batch->supplier)->name,
                'time' => Carbon::parse($batch->received_date)->diffForHumans(),
                'details' => $batch->batch_number,
                'quantity' => BatchItem::where('batch_id', $batch->id)->sum('received_quantity'),
            ];
        });
        $recentReturns = PurchaseReturn::latest('return_date')->take(5)->get()->map(function($ret) {
            return [
                'type' => 'return',
                'message' => 'Trả hàng: ' . optional($ret->supplier)->name,
                'time' => Carbon::parse($ret->return_date)->diffForHumans(),
                'details' => $ret->return_number,
                'quantity' => $ret->total_items_returned,
            ];
        });
        $recentActivities = $recentImports->merge($recentReturns)->sortByDesc('time')->take(5)->values();

        // Cảnh báo tồn kho thấp
        $alerts = Product::with('category')
            ->get()
            ->map(function($product) {
                $stock = BatchItem::where('product_id', $product->id)->sum('current_quantity');
                $urgency = $stock < $product->min_stock_level ? ($stock < ($product->min_stock_level / 2) ? 'high' : 'medium') : null;
                if ($urgency) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'current_stock' => $stock,
                        'min_stock' => $product->min_stock_level,
                        'category' => optional($product->category)->name,
                        'urgency' => $urgency,
                    ];
                }
                return null;
            })
            ->filter()
            ->values();

        return Inertia::render('Dashboard', [
            'overview' => $overview,
            'charts' => [
                'imports' => $imports,
                'returns' => $returns,
                'inventory' => $inventory,
                'topProducts' => $topProducts,
            ],
            'recentActivities' => $recentActivities,
            'alerts' => $alerts,
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
