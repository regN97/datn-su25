<?php

namespace App\Http\Controllers\Cashier;

use App\Models\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class CustomerLookupController extends Controller
{
    /**
     * Display a listing of the customers.
     */
    public function index()
    {
        // Lấy 50 khách hàng mới nhất và phân trang 5 khách hàng mỗi trang.
        $customers = Customer::query()
            ->latest()
            ->take(50)
            ->paginate(5);
        
        // Cấu trúc lại dữ liệu để gửi về frontend
        $customers->setCollection($customers->getCollection()->map(function ($customer) {
            return [
                'id' => $customer->id,
                'customer_name' => $customer->customer_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'wallet' => $customer->wallet,
                'created_at' => $customer->created_at->format('d/m/Y H:i'),
            ];
        }));

        Log::info('Customers structure (index):', $customers->toArray());

        return Inertia::render('cashier/pos/CustomerLookup', [
            'customers' => $customers,
            'query' => '',
        ]);
    }

    /**
     * Search for customers.
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'nullable|string|max:50',
        ]);

        $query = $request->input('query');

        $customers = Customer::query()
            ->when($query, function ($q) use ($query) {
                // Tìm kiếm theo tên, email, hoặc số điện thoại
                $q->where('customer_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            })
            ->latest()
            ->take(50)
            ->paginate(5);

        // Cấu trúc lại dữ liệu tương tự như index
        $customers->setCollection($customers->getCollection()->map(function ($customer) {
            return [
                'id' => $customer->id,
                'customer_name' => $customer->customer_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'wallet' => $customer->wallet,
                'created_at' => $customer->created_at->format('d/m/Y H:i'),
            ];
        }));

        Log::info('Customers structure (search):', $customers->toArray());

        return Inertia::render('cashier/pos/CustomerLookup', [
            'customers' => $customers,
            'query' => $query,
        ]);
    }

    /**
     * Get a customer with all their bills.
     */
    public function show(Request $request, Customer $customer)
    {
        $date = $request->input('date');

        $customer->load(['bills' => function ($query) use ($date) {
            $query->with(['details.product', 'details.batch', 'cashier'])
                  ->when($date, function ($q) use ($date) {
                      $q->whereDate('created_at', '=', $date);
                  })
                  ->latest()
                  ->take(20);
        }]);

        // Cấu trúc lại dữ liệu hóa đơn chi tiết
        $customer->bills->transform(function ($bill) {
            return [
                'id' => $bill->id,
                'bill_number' => $bill->bill_number,
                'total_amount' => $bill->total_amount,
                'payment_method' => $bill->payment_method,
                'cashier_name' => $bill->cashier?->name,
                'created_at' => $bill->created_at->format('d/m/Y H:i'),
                'details' => $bill->details->map(function ($detail) {
                    return [
                        'product_name' => $detail->p_name,
                        'quantity' => $detail->quantity,
                        'unit_price' => $detail->unit_price,
                        'subtotal' => $detail->subtotal,
                        'batch_number' => $detail->batch?->batch_number,
                    ];
                }),
            ];
        });

        Log::info('Customer with bills structure:', $customer->toArray());

        return Inertia::render('cashier/pos/CustomerShow', [
            'customer' => [
                'id' => $customer->id,
                'customer_name' => $customer->customer_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'wallet' => $customer->wallet,
                'created_at' => $customer->created_at->format('d/m/Y H:i'),
                'bills' => $customer->bills,
            ],
            // Thêm prop mới cho ngày
            'date' => $date,
        ]);
    }
}