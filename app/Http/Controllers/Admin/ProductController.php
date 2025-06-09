<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use App\Models\ProductSupplier;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreProductRequest;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with(['category', 'unit', 'suppliers'])->get();
        $categories = Category::all();
        $units = ProductUnit::all();
        $suppliers = ProductSupplier::all();
        return Inertia::render('admin/products/Index')->with([
            'products' => $products,
            'categories' => $categories,
            'units' => $units,
            'suppliers' => $suppliers,
        ]);
    }


    public function create()
    {
        return Inertia::render('admin/products/Create', [
            'suppliers' => Supplier::all(['id', 'name']),
            'categories' => Category::with('children')->whereNull('parent_id')->get(['id', 'name', 'parent_id']),
            'product_units' => ProductUnit::all(['id', 'name']),
            'csrf_token' => csrf_token(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'sku' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('products', 'sku'),
            ],
            'barcode' => [
                'nullable',
                'string',
                'min:5',
                'max:255',
                Rule::unique('products', 'barcode')->whereNotNull('barcode'),
            ],
            'description' => 'nullable|string|max:5000',
            'category_id' => 'required|integer|min:1|exists:categories,id',
            'unit_id' => 'required|integer|min:1|exists:product_units,id',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0|gt:purchase_price',
            'is_active' => 'required|boolean',
            'min_stock_level' => 'nullable|integer|min:0',
            'max_stock_level' => 'nullable|integer|min:0|gte:min_stock_level',
            'image_input_type' => 'required|in:file,url',
            'image_url' => Rule::requiredIf($request->input('image_input_type') === 'url') . '|nullable|string|url|max:2048',
            'image_file' => Rule::requiredIf($request->input('image_input_type') === 'file') . '|nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'selected_supplier_ids' => 'required|array|min:1',
            'selected_supplier_ids.*' => 'required|integer|exists:suppliers,id',
        ], [
            'required' => 'Trường :attribute là bắt buộc.',
            'string' => 'Trường :attribute phải là chuỗi ký tự.',
            'min' => 'Trường :attribute phải có ít nhất :min ký tự.',
            'max' => 'Trường :attribute không được vượt quá :max ký tự.',
            'numeric' => 'Trường :attribute phải là số.',
            'integer' => 'Trường :attribute phải là số nguyên.',
            'boolean' => 'Trường :attribute phải là giá trị boolean (true/false).',
            'url' => 'Đường dẫn ảnh không hợp lệ.',
            'image' => 'Tệp được tải lên phải là hình ảnh.',
            'mimes' => 'Ảnh chỉ chấp nhận định dạng JPG, JPEG, PNG, GIF hoặc WEBP.',
            'in' => 'Giá trị của trường :attribute không hợp lệ.',
            'array' => 'Trường :attribute phải là danh sách.',
            'exists' => 'Giá trị đã chọn cho :attribute không tồn tại.',
            'unique' => ':Attribute này đã tồn tại, vui lòng chọn giá trị khác.',
            'gt' => 'Giá bán phải lớn hơn giá nhập.',
            'gte' => 'Tồn kho tối đa phải lớn hơn hoặc bằng tồn kho tối thiểu.',

            'selected_supplier_ids.min' => 'Cần chọn ít nhất :min nhà cung cấp.',
            'image_file.max' => 'Kích thước ảnh không được vượt quá :max KB (2MB).',
            'image_url.max' => 'Đường dẫn ảnh không được vượt quá :max ký tự.',
        ], [
            'name' => 'tên sản phẩm',
            'sku' => 'mã SKU',
            'barcode' => 'mã vạch',
            'description' => 'mô tả',
            'category_id' => 'danh mục',
            'unit_id' => 'đơn vị tính',
            'purchase_price' => 'giá nhập',
            'selling_price' => 'giá bán',
            'min_stock_level' => 'tồn kho tối thiểu',
            'max_stock_level' => 'tồn kho tối đa',
            'is_active' => 'trạng thái',
            'image_url' => 'đường dẫn ảnh',
            'image_file' => 'ảnh sản phẩm',
            'image_input_type' => 'kiểu nhập ảnh',
            'selected_supplier_ids' => 'nhà cung cấp',
            'selected_supplier_ids.*' => 'ID nhà cung cấp',
        ]);
        $uploadedFilePath = null;
        if ($request->input('image_input_type') === 'file' && $request->hasFile('image_file')) {
            $uploadedFilePath = $request->file('image_file')->store('product_images', 'public');
            $data['image_url'] = Storage::url($uploadedFilePath);
            unset($data['image_file']);
        } elseif ($request->input('image_input_type') === 'url' && !empty($data['image_url'])) {
            unset($data['image_file']);
        } else {
            $data['image_url'] = null;
            unset($data['image_file']);
        }

        $selectedSupplierIds = $data['selected_supplier_ids'];
        unset($data['selected_supplier_ids']);

        $product = Product::create($data);

        // Đồng bộ nhà cung cấp sau khi tạo sản phẩm
        $product->suppliers()->sync($selectedSupplierIds);

        return redirect()->route('admin.products.index')->with('success', 'Sản phẩm đã được tạo thành công.');

    }



    public function show(string $id)
    {
        //
    }



    public function edit($id)
    {
        // Dữ liệu mẫu sản phẩm
        $product = Product::with('suppliers')->findOrFail($id);
        $categories = Category::with('children')->whereNull('parent_id')->get(['id', 'name', 'parent_id']);
        $units = ProductUnit::all(['id', 'name']);
        $suppliers = Supplier::all(['id', 'name']);
        $selectedSupplierIds = $product->suppliers->pluck('id')->toArray();
        $imageUrl = $product->image_url ? Storage::url($product->image_url) : null;
        $productSuppliers = $product->suppliers()->pluck('suppliers.id')->toArray();
        return Inertia::render('admin/products/Edit', [
            'product' => $product,
            'categories' => $categories,
            'units' => $units,
            'suppliers' => $suppliers,
            'selectedSupplierIds' => $selectedSupplierIds,
            'imageUrl' => $imageUrl,
            'csrf_token' => csrf_token(),
            'productSuppliers' => $productSuppliers,
        ]);
    }


    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, string $id)
    {
        // Validate dữ liệu với thông báo lỗi tiếng Việt
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'category_id' => 'required|integer',
            'unit_id' => 'required|integer',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'max_stock_level' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'image_url' => 'nullable|string',
            'image_file' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'image_type' => 'required|in:url,upload',
            'selected_supplier_ids' => 'required|array|min:1',
            'selected_supplier_ids.*' => 'exists:suppliers,id',
        ], [
            // ... các thông báo lỗi ...
        ], [
            // ... các tên trường ...
        ]);

        // Lấy sản phẩm từ DB
        $product = Product::findOrFail($id);

        // Xử lý ảnh
        if ($data['image_type'] === 'upload' && $request->hasFile('image_file')) {
            // Xóa ảnh cũ nếu là file local
            if ($product->image_url && str_starts_with($product->image_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $product->image_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            // Lưu ảnh mới
            $uploadedFilePath = $request->file('image_file')->store('product_images', 'public');
            $data['image_url'] = Storage::url($uploadedFilePath);
        } elseif ($data['image_type'] === 'url' && !empty($data['image_url'])) {
            // Nếu là url, xóa file cũ nếu là file local
            if ($product->image_url && str_starts_with($product->image_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $product->image_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            // Giữ nguyên image_url đã validate
        } else {
            // Không có ảnh mới, giữ nguyên ảnh cũ
            $data['image_url'] = $product->image_url;
        }

        unset($data['image_file'], $data['image_type']);

        // Cập nhật thông tin sản phẩm
        $product->update($data);

        // Cập nhật nhà cung cấp liên kết
        if (isset($data['selected_supplier_ids'])) {
            $product->suppliers()->sync($data['selected_supplier_ids']);
        }

        return redirect()->route('admin.products.index')->with('success', 'Cập nhật sản phẩm thành công!');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
