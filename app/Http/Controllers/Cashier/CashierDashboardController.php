<?php

namespace App\Http\Controllers\Cashier;

use Carbon\Carbon;
use App\Models\Bill;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\BatchItem;
use App\Models\UserShift;
use App\Models\WorkShift;
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

        $workShifts = WorkShift::select('id', 'name', 'start_time', 'end_time')->get();

        $currentShift = null;
        $currentCashier = 'Chưa có nhân viên trực';
        $shiftRevenue = [];
        $shiftBills = [];

        foreach ($workShifts as $shift) {
            $startTimeString = trim($shift->start_time);
            $endTimeString = trim($shift->end_time);

            if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $startTimeString)) {
                Log::error('Invalid start_time format for shift ' . $shift->name . ': ' . $startTimeString);
                $startTimeString = '00:00:00';
            }
            if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $endTimeString)) {
                Log::error('Invalid end_time format for shift ' . $shift->name . ': ' . $endTimeString);
                $endTimeString = '23:59:59';
            }

            $startTime = Carbon::createFromFormat('Y-m-d H:i:s', $today->toDateString() . ' ' . $startTimeString);
            $endTime = Carbon::createFromFormat('Y-m-d H:i:s', $today->toDateString() . ' ' . $endTimeString);

            if ($endTime->lessThan($startTime)) {
                if ($now->hour < $endTime->hour) {
                    $startTime->subDay();
                    $endTime->subDay();
                } else {
                    $endTime->addDay();
                }
            }

            if ($now->between($startTime, $endTime)) {
                $currentShift = $shift;

                $revenue = Bill::whereBetween('created_at', [$startTime, $endTime])
                    ->sum('total_amount');

                $bills = Bill::whereBetween('created_at', [$startTime, $endTime])
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

                $shiftRevenue[$shift->id] = [
                    'shift_name' => $shift->name,
                    'revenue' => number_format($revenue, 0, ',', '.') . 'đ',
                ];
                $shiftBills[$shift->id] = [
                    'shift_name' => $shift->name,
                    'bills' => $bills,
                ];
            }
        }

        $activeSession = CashRegisterSession::whereNull('closed_at')
            ->whereDate('opened_at', $today)
            ->with('user')
            ->first();

        if ($activeSession) {
            $currentCashier = $activeSession->user->name;

            if ($activeSession->user_shift_id) {
                $userShift = UserShift::where('id', $activeSession->user_shift_id)
                    ->with('workShift')
                    ->first();
                if ($userShift) {
                    $currentShift = $userShift->workShift;
                }
            }
        }

        $todayRevenue = Bill::whereDate('created_at', $today)->sum('total_amount');
        $todayOrders = Bill::whereDate('created_at', $today)->count();
        $totalStock = BatchItem::sum('current_quantity');

        $todayBills = Bill::whereDate('created_at', $today)
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
            'currentShift' => $currentShift ? $currentShift->name : 'Chưa xác định ca',
            'todayBills' => $todayBills,
            'allProducts' => $allProducts,
            'shiftRevenue' => array_values($shiftRevenue),
            'shiftBills' => array_values($shiftBills),
        ]);

    }
    public function addToCart(Request $request)
    {
        $productId = $request->input('product_id');
        $product = Product::findOrFail($productId);

        // Logic thêm sản phẩm vào giỏ hàng (lưu vào session)
        $cart = session()->get('cart', []);
        $cart[$product->id] = [
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->selling_price,
            'quantity' => ($cart[$product->id]['quantity'] ?? 0) + 1,
        ];
        session()->put('cart', $cart);

        // Gửi thông báo tới tất cả user có role_id = 1
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