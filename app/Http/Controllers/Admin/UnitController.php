<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ProductUnit;
use App\Models\Product;
use Inertia\Inertia;

class UnitController extends Controller
{
    // Hiển thị danh sách đơn vị tính
    public function index()
    {
        $units = ProductUnit::all();
        return Inertia::render('admin/units/Index', [
            'units' => $units,
        ]);
    }

    // Trả về form thêm mới đơn vị tính
    public function create()
    {
        return Inertia::render('admin/units/Create');
    }

    // Lưu đơn vị tính mới
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:product_units,name',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ], [
            'name.required' => 'Tên đơn vị không được để trống.',
            'name.string' => 'Tên đơn vị phải là chuỗi ký tự.',
            'name.max' => 'Tên đơn vị không được vượt quá 255 ký tự.',
            'name.unique' => 'Tên đơn vị đã tồn tại.',
            'description.max' => 'Mô tả không được vượt quá 1000 ký tự.',
        ]);

        $unit = ProductUnit::create($validated);

        return redirect()->route('admin.units.index')->with('success', 'Thêm đơn vị tính thành công!');
    }

    // Trả về form sửa đơn vị tính
    public function edit($id)
    {
        $unit = ProductUnit::findOrFail($id);
        return Inertia::render('admin/units/Edit', [
            'unit' => $unit,
        ]);
    }

    // Cập nhật đơn vị tính
    public function update(Request $request, $id)
    {
        $unit = ProductUnit::findOrFail($id);
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:product_units,name,' . $unit->id,
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ], [
            'name.required' => 'Tên đơn vị không được để trống.',
            'name.string' => 'Tên đơn vị phải là chuỗi ký tự.',
            'name.max' => 'Tên đơn vị không được vượt quá 255 ký tự.',
            'name.unique' => 'Tên đơn vị đã tồn tại.',
            'description.max' => 'Mô tả không được vượt quá 1000 ký tự.',
        ]);
        $unit->update($validated);
        return redirect()->route('admin.units.index')->with('success', 'Cập nhật đơn vị tính thành công!');
    }

    // Xóa đơn vị tính
    public function destroy($id)
    {
        $unit = ProductUnit::findOrFail($id);
        $products = Product::where('unit_id', $id)->get();
        if ($products->count() > 0) {
            $productNames = $products->pluck('name')->take(3)->join(', ');
            if ($products->count() > 3) {
                $productNames .= ' và ' . ($products->count() - 3) . ' sản phẩm khác';
            }
            return redirect()->back()->withErrors([
                'unit_delete' => "Không thể xóa đơn vị tính này vì đang được sử dụng bởi các sản phẩm: {$productNames}"
            ]);
        }
        $unit->delete();
        return redirect()->route('admin.units.index')->with('success', 'Xóa đơn vị tính thành công!');
    }
}
