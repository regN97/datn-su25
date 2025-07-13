<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function index(){
         $customers = Customer::all();
        return inertia('admin/customers/Index', [
            'customers' => $customers,
        ]);
    }
    public function create()
    {
        return inertia('admin/customers/Create');
    }

    public function store(Request $request)
    {
        $rules = [
            'customer_name' => ['required', 'string', 'max:30', Rule::unique('customers', 'customer_name')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')],
            'phone' => ['required', 'string', 'max:20', Rule::unique('customers', 'phone')],
            'address' => ['nullable', 'string'],
            'wallet' => ['nullable', 'integer', 'min:0'],
        ];

        $messages = [
            'customer_name.required' => 'Tên khách hàng là bắt buộc.',
            'customer_name.string' => 'Tên khách hàng phải là chuỗi ký tự.',
            'customer_name.max' => 'Tên khách hàng không được vượt quá :max ký tự.',
            'customer_name.unique' => 'Tên khách hàng này đã tồn tại.',

            'email.email' => 'Email phải là định dạng email hợp lệ.',
            'email.max' => 'Email không được vượt quá :max ký tự.',
            'email.unique' => 'Email này đã tồn tại.',

            'phone.required' => 'Số điện thoại là bắt buộc.',
            'phone.string' => 'Số điện thoại phải là chuỗi ký tự.',
            'phone.max' => 'Số điện thoại không được vượt quá :max ký tự.',
            'phone.unique' => 'Số điện thoại này đã tồn tại.',

            'address.string' => 'Địa chỉ phải là chuỗi ký tự.',

            'wallet.integer' => 'Ví tiền phải là số nguyên.',
            'wallet.min' => 'Ví tiền không được nhỏ hơn 0.',
        ];

        $validatedData = $request->validate($rules, $messages);

        Customer::create($validatedData);

        return Inertia::render('admin/customers/Create', [
            'status' => 'success',
            'message' => 'Thêm khách hàng thành công!'
        ]);
    }
    public function edit(Customer $customer)
    {
        return Inertia::render('admin/customers/Edit', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $rules = [
            'customer_name' => ['required', 'string', 'max:30', Rule::unique('customers', 'customer_name')->ignore($customer->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer->id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('customers', 'phone')->ignore($customer->id)],
            'address' => ['nullable', 'string'],
            'wallet' => ['nullable', 'integer', 'min:0'],
        ];

        $messages = [
            'customer_name.required' => 'Tên khách hàng là bắt buộc.',
            'customer_name.string' => 'Tên khách hàng phải là chuỗi ký tự.',
            'customer_name.max' => 'Tên khách hàng không được vượt quá :max ký tự.',
            'customer_name.unique' => 'Tên khách hàng này đã tồn tại.',

            'email.email' => 'Email phải là định dạng email hợp lệ.',
            'email.max' => 'Email không được vượt quá :max ký tự.',
            'email.unique' => 'Email này đã tồn tại.',

            'phone.required' => 'Số điện thoại là bắt buộc.',
            'phone.string' => 'Số điện thoại phải là chuỗi ký tự.',
            'phone.max' => 'Số điện thoại không được vượt quá :max ký tự.',
            'phone.unique' => 'Số điện thoại này đã tồn tại.',

            'address.string' => 'Địa chỉ phải là chuỗi ký tự.',

            'wallet.integer' => 'Ví tiền phải là số nguyên.',
            'wallet.min' => 'Ví tiền không được nhỏ hơn 0.',
        ];

        $validatedData = $request->validate($rules, $messages);

        $customer->update($validatedData);

        return Inertia::render('admin/customers/Edit', [
            'customer' => $customer,
            'status' => 'success',
            'message' => 'Cập nhật khách hàng thành công!'
        ]);
    }

    
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('admin.customers.index')->with('status', 'success')->with('message', 'Khách hàng đã được xóa thành công.');
    }

    public function trashed()
    {
        $customers = Customer::onlyTrashed()->get();
        return Inertia::render('admin/customers/Trashed', [
            'customers' => $customers,
        ]);
    }
    public function restore($id)
    {
        $customer = Customer::onlyTrashed()->findOrFail($id);
        $customer->restore();

        return redirect()->route('admin.customers.index')->with('status', 'success')->with('message', 'Khách hàng đã được khôi phục thành công.');
    }

    public function forceDelete($id)
    {
        $customer = Customer::onlyTrashed()->findOrFail($id);
        $customer->forceDelete();

        return redirect()->route('admin.customers.trashed')->with('status', 'success')->with('message', 'Khách hàng đã được xóa vĩnh viễn.');
    }
}
