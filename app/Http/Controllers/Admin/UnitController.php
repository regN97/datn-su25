<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ProductUnit;
use Inertia\Inertia;

class UnitController extends Controller
{
    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => [
            'required',
            'string',
            'max:255',
            'unique:product_units,name',
        ],
    ], [
        'name.required' => 'Tên đơn vị không được để trống.',
        'name.string' => 'Tên đơn vị phải là chuỗi ký tự.',
        'name.max' => 'Tên đơn vị không được vượt quá 255 ký tự.',
        'name.unique' => 'Tên đơn vị đã tồn tại.',
    ]);

    $unit = ProductUnit::create($validated);

    // Nếu là request AJAX/Inertia, trả về JSON
    if ($request->wantsJson() || $request->header('X-Inertia')) {
        return response()->json(['unit' => $unit]);
    }

    // Nếu không, vẫn render như cũ
    return Inertia::render('admin/products/Create', [
        'unit' => $unit,
        'product_units' => ProductUnit::all(),
    ]);
}
}
