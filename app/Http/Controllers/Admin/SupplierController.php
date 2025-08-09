<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $suppliers = Supplier::all();

        return Inertia::render('admin/suppliers/Index')->with([
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/suppliers/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) // Change SupplierRequest to Request
    {
        // Define validation rules
        $rules = [
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')],
            'contact_person' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'contact_person')],
            'email' => ['required', 'email', 'max:255', Rule::unique('suppliers', 'email')],
            'phone' => ['required', 'string', 'max:20', Rule::unique('suppliers', 'phone')],
            'address' => ['required', 'string'],
        ];

        // Define custom error messages
        $messages = [
            'name.required' => 'Tên nhà cung cấp là bắt buộc.',
            'name.string' => 'Tên nhà cung cấp phải là chuỗi ký tự.',
            'name.max' => 'Tên nhà cung cấp không được vượt quá :max ký tự.',
            'name.unique' => 'Tên nhà cung cấp này đã tồn tại.',

            'contact_person.required' => 'Người liên hệ là bắt buộc.',
            'contact_person.string' => 'Người liên hệ phải là chuỗi ký tự.',
            'contact_person.max' => 'Người liên hệ không được vượt quá :max ký tự.',
            'contact_person.unique' => 'Người liên hệ này đã tồn tại.',

            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email phải là định dạng email hợp lệ.',
            'email.max' => 'Email không được vượt quá :max ký tự.',
            'email.unique' => 'Email này đã tồn tại.',

            'phone.required' => 'Số điện thoại là bắt buộc.',
            'phone.string' => 'Số điện thoại phải là chuỗi ký tự.',
            'phone.max' => 'Số điện thoại không được vượt quá :max ký tự.',
            'phone.unique' => 'Số điện thoại này đã tồn tại.',
            
            'address.required' => 'Địa chỉ là bắt buộc.',
            'address.string' => 'Địa chỉ phải là chuỗi ký tự.',
        ];

        // Validate the request
        $validatedData = $request->validate($rules, $messages);

        // Create the supplier using the validated data
        Supplier::create($validatedData);

        return redirect()->route('admin.suppliers.index')->with('success', 'Thêm mới nhà cung cấp thành công!');
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
public function edit($id)
{
    $supplier = Supplier::findOrFail($id);
    return Inertia::render('admin/suppliers/Edit', [
        'supplier' => $supplier,
    ]);
}
public function update(Request $request, string $id)
{
    $supplier = Supplier::findOrFail($id);

    $data = $request->validate([
        'name' => 'required|string|max:255',
        'contact_person' => 'required|string|max:255',
        'email' => ' required|email|max:255',
        'phone' => ' required|string|max:20',
        'address' => 'nullable|string|max:255',
    ], [
        'name.required' => 'Tên nhà cung cấp là bắt buộc.',
        'contact_person.required' => 'Người liên hệ là bắt buộc.',
        'email.required' => 'Email là bắt buộc.',
        'phone.required' => 'Số điện thoại là bắt buộc.',
        'email.email' => 'Email không đúng định dạng.',
        'phone.string' => 'Số điện thoại không hợp lệ.',
        'address.string' => 'Địa chỉ không hợp lệ.',
    ]);

    $supplier->update($data);

    return redirect()->route('admin.suppliers.index')->with('success', 'Cập nhật nhà cung cấp thành công!');
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->products()->exists()) {
            return redirect()->route('admin.suppliers.index')
                ->with('error', 'Không thể xóa nhà cung cấp vì vì vẫn còn sản phẩm đang sử dụng nhà cung cấp này.');
        }
        $supplier->delete();

        return redirect()->back()->with('success', 'Nhà cung cấp đã được xóa mềm!');
    }

    /**
     * Display a listing of trashed resources.
     */
    public function trashed()
    {
        $suppliers = Supplier::onlyTrashed()->get();

        return Inertia::render('admin/suppliers/Trashed', [
            'suppliers' => $suppliers,
        ]);
    }


    /**
     * Restore the specified trashed resource.
     */
    public function restore(string $id)
    {
        $supplier = Supplier::onlyTrashed()->findOrFail($id);
        $supplier->restore();

        return redirect()->back()->with('success', 'Nhà cung cấp đã được khôi phục!');
    }

    /**
     * Permanently remove the specified resource from storage.
     */
    public function forceDelete(string $id)
    {
        $supplier = Supplier::onlyTrashed()->findOrFail($id);
        $supplier->forceDelete();

        return redirect()->back()->with('success', 'Nhà cung cấp đã được xóa vĩnh viễn!');
    }
}
