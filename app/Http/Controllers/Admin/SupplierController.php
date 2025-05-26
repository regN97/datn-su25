<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;
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
    public function store(SupplierRequest $request)
    {
        Supplier::create($request->validated());

        return Inertia::render('admin/suppliers/Create', [
            'status' => 'success',
            'message' => 'Thêm nhà cung cấp thành công!'
        ]);
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
