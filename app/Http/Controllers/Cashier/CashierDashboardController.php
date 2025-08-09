<?php

namespace App\Http\Controllers\Cashier;

use Carbon\Carbon;
use App\Models\Bill;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\BatchItem;
use App\Models\UserShift;
use App\Models\BillDetail;
use Illuminate\Http\Request;
use App\Models\CashRegisterSession;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Notifications\ProductAddedToCart;

class CashierDashboardController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $today = Carbon::today();

        $currentShift = UserShift::where('user_id', Auth::id())
            ->whereDate('date', $today)
            ->where('status', 'CHECKED_IN')
            ->whereNotNull('check_in')
            ->where(function ($query) use ($now) {
                $query->whereNull('check_out')
                      ->orWhere('check_out', '>=', $now);
            })
            ->with('user')
            ->first();

        $currentCashier = 'Chưa có nhân viên trực';
        $shiftRevenue = [];
        $shiftBills = [];
        $todayOrders = 0;
        $todayRevenue = 0;
        $todayBills = [];

        if ($currentShift) {
            $startTime = Carbon::parse($currentShift->check_in);
            $endTime = $currentShift->check_out ? Carbon::parse($currentShift->check_out) : $now;

            $todayRevenue = Bill::whereBetween('created_at', [$startTime, $endTime])
                ->sum('total_amount');

            $todayOrders = Bill::whereBetween('created_at', [$startTime, $endTime])
                ->count();

            $todayBills = Bill::whereBetween('created_at', [$startTime, $endTime])
                ->with('customer')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($bill) {
                    return [
                        'bill_number' => $bill->bill_number,
                        'customer_name' => $bill->customer ? $bill->customer->customer_name : 'Khách lẻ',
                        'total_amount' => number_format($bill->total_amount, 0, ',', '.') . 'đ',
                        'created_at' => $bill->created_at->format('H:i'),
                    ];
                })->toArray();

            $shiftRevenue = [
                [
                    'shift_name' => 'Ca của ' . $currentShift->user->name,
                    'revenue' => number_format($todayRevenue, 0, ',', '.') . 'đ',
                ]
            ];
            $shiftBills = [
                [
                    'shift_name' => 'Ca của ' . $currentShift->user->name,
                    'bills' => $todayBills,
                ]
            ];
        } else {
            Log::info('No active shift found for user ' . Auth::id() . ' on ' . $today->toDateString());
        }

        $activeSession = CashRegisterSession::whereNull('closed_at')
            ->whereDate('opened_at', $today)
            ->where('user_id', Auth::id())
            ->with('user')
            ->first();

        if ($activeSession) {
            $currentCashier = $activeSession->user->name;
        }

        $totalStock = BatchItem::sum('current_quantity');

        $allProducts = Product::select('id', 'name', 'sku', 'selling_price')
            ->with(['billDetails' => function ($query) use ($today) {
                $query->select('product_id')
                    ->selectRaw('SUM(quantity) as total_quantity')
                    ->whereDate('created_at', $today)
                    ->groupBy('product_id');
            }])
            ->get()
            ->map(function ($product) use ($today) {
                $stock = BatchItem::where('product_id', $product->id)
                    ->whereDate('created_at', '<=', $today)
                    ->sum('current_quantity');

                $totalQuantity = $product->billDetails->first()->total_quantity ?? 0;

                return [
                    'id' => $product->id,
                    'name' => trim($product->name),
                    'sku' => trim($product->sku),
                    'stock' => (int) $stock,
                    'price' => number_format($product->selling_price, 0, ',', '.') . 'đ',
                    'total_quantity' => (int) $totalQuantity,
                ];
            })->toArray();

        return Inertia::render('cashier/Dashboard', [
            'todayRevenue' => number_format($todayRevenue, 0, ',', '.') . 'đ',
            'todayOrders' => $todayOrders,
            'totalStock' => $totalStock,
            'currentCashier' => $currentCashier,
            'currentShift' => $currentShift ? 'Ca của ' . $currentShift->user->name : 'Chưa xác định ca',
            'todayBills' => $todayBills,
            'allProducts' => $allProducts,
            'shiftRevenue' => $shiftRevenue,
            'shiftBills' => $shiftBills,
        ]);
    }

    public function addToCart(Request $request)
    {
        $productId = $request->input('product_id');
        $product = Product::findOrFail($productId);

        $cart = session()->get('cart', []);
        $cart[$product->id] = [
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->selling_price,
            'quantity' => ($cart[$product->id]['quantity'] ?? 0) + 1,
        ];
        session()->put('cart', $cart);

        $admins = User::where('role_id', 1)->get();
        foreach ($admins as $admin) {
            $admin->notify(new ProductAddedToCart([
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->selling_price,
            ], Auth::user()));
        }

        return response()->json([
            'message' => 'Sản phẩm đã được thêm vào giỏ hàng!',
            'cart' => $cart,
        ]);
    }
}