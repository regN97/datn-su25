<?php

namespace App\Http\Controllers\Cashier;

use Inertia\Inertia;
use App\Models\Product;
use App\Models\Customer;
use App\Models\BatchItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class POSController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->select('id', 'name', 'sku', 'barcode', 'selling_price', 'image_url', 'category_id')
            ->where('is_active', 1)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->selling_price,
                    'image' => $product->image_url ?? '/storage/piclumen-1747750187180.png',
                    'category' => $product->category ? $product->category->name : 'Không có danh mục',
                    'stock' => $this->getProductStock($product->id),
                ];
            });

        $customers = Customer::select('id', 'customer_name', 'email', 'phone', 'address', 'wallet')
            ->whereNull('deleted_at')
            ->orderBy('customer_name', 'asc')
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->customer_name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'address' => $customer->address,
                    'wallet' => $customer->wallet ?? 0,
                ];
            });

        return Inertia::render('cashier/POS', [
            'products' => $products,
            'customers' => $customers,
        ]);
    }

    public function storeCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'phone' => 'required|string|regex:/^\d{10,11}$/|unique:customers,phone',
            'address' => 'nullable|string|max:500',
            'wallet' => 'nullable|numeric|min:0',
        ], [
            'email.unique' => 'Email này đã được sử dụng. Vui lòng chọn email khác.',
            'phone.unique' => 'Số điện thoại này đã được sử dụng. Vui lòng chọn số khác.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput();
        }

        try {
            $customer = Customer::create($validator->validated());

            $newCustomer = [
                'id' => $customer->id,
                'name' => $customer->customer_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'wallet' => $customer->wallet ?? 0,
            ];

            $customers = Customer::select('id', 'customer_name', 'email', 'phone', 'address', 'wallet')
                ->whereNull('deleted_at')
                ->orderBy('customer_name', 'asc')
                ->get()
                ->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->customer_name,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'address' => $customer->address,
                        'wallet' => $customer->wallet ?? 0,
                    ];
                });

            return back()->with([
                'success' => 'Khách hàng đã được thêm thành công!',
                'newCustomer' => $newCustomer,
                'customers' => $customers,
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        } catch (\Exception $e) {
            return back()->withErrors(['server' => 'Có lỗi xảy ra khi thêm khách hàng. Vui lòng thử lại.'])->withInput();
        }
    }

    private function getProductStock($productId)
    {
        return BatchItem::where('product_id', $productId)
            ->where('inventory_status', 'active')
            ->sum('current_quantity');
    }
}