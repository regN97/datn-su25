<?php

namespace App\Http\Controllers\Admin;

use HRTime\Unit;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\ProductUnit;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ProductSupplier;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
        $all_suppliers = Supplier::all();
        return Inertia::render('admin/products/Index')->with([
            'products' => $products,
            'categories' => $categories,
            'units' => $units,
            'suppliers' => $suppliers,
            'allSuppliers' => $all_suppliers,
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

    public function store(ProductRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            // Thiết lập giá trị mặc định cho tồn kho
            $data['min_stock_level'] = $data['min_stock_level'] ?? 20;
            $data['max_stock_level'] = $data['max_stock_level'] ?? 200;

            // Kiểm tra giá bán so với giá nhập
            if (isset($data['purchase_prices']) && is_array($data['purchase_prices'])) {
                $purchasePrices = array_filter($data['purchase_prices'], fn($price) => !is_null($price));
                if (!empty($purchasePrices)) {
                    $minPurchasePrice = min($purchasePrices);
                    if ($data['selling_price'] < $minPurchasePrice) {
                        return back()->withErrors([
                            'selling_price' => 'Giá bán phải lớn hơn hoặc bằng giá nhập thấp nhất (' . $minPurchasePrice . ')'
                        ])->withInput();
                    }
                }
            }

            // Parse JSON nếu truyền từ FormData
            if (is_string($request->input('selected_supplier_ids'))) {
                $data['selected_supplier_ids'] = json_decode($request->input('selected_supplier_ids'), true);
            }
            if (is_string($request->input('purchase_prices'))) {
                $data['purchase_prices'] = json_decode($request->input('purchase_prices'), true);
            }

            // Sinh SKU tự động
            $data['sku'] = $this->generateAutoSku();

            // Xử lý ảnh theo kiểu người dùng chọn
            if (isset($data['image_input_type'])) {
                if ($data['image_input_type'] === 'file' && $request->hasFile('image_file')) {
                    $file = $request->file('image_file');
                    if ($file && $file->isValid() && $file->getRealPath()) {
                        $uploadedFilePath = $file->store('product_images', 'public');
                        $data['image_url'] = Storage::url($uploadedFilePath);
                    } else {
                        return back()->withErrors(['image_file' => 'File ảnh không hợp lệ hoặc đã bị xóa.'])->withInput();
                    }
                } elseif ($data['image_input_type'] === 'url' && !empty($data['image_url'])) {
                    if (Str::contains($data['image_url'], 'google.com/imgres')) {
                        parse_str(parse_url($data['image_url'], PHP_URL_QUERY), $query);
                        $data['image_url'] = $query['imgurl'] ?? $data['image_url'];
                    }
                } else {
                    $data['image_url'] = 'default-product.jpg';
                }
            }

            // Xóa các field phụ không cần lưu trong CSDL
            unset($data['image_file'], $data['image_input_type']);

            // Tách supplier & giá nhập
            $supplierIds = $data['selected_supplier_ids'] ?? [];
            $purchasePrices = $data['purchase_prices'] ?? [];
            unset($data['selected_supplier_ids'], $data['purchase_prices']);

            // Tạo sản phẩm
            $product = Product::create($data);

            // Gắn nhà cung cấp & giá nhập
            $syncData = [];
            foreach ($supplierIds as $supplierId) {
                $syncData[$supplierId] = ['purchase_price' => $purchasePrices[$supplierId] ?? null];
            }
            $product->suppliers()->sync($syncData);

            return redirect()->route('admin.products.index')->with('success', 'Sản phẩm đã được tạo thành công.');
        } catch (\Exception $e) {
            Log::error('Error creating product: ' . $e->getMessage());
            return back()->with([
                'error' => 'Đã xảy ra lỗi khi tạo sản phẩm. Vui lòng thử lại sau.',
            ])->withInput();
        }
    }


    public function show($id)
    {
        $product = Product::findOrFail($id);

        return Inertia::render('admin/products/Show')->with([
            'product' => $product,
            'unit' => $product->unit,
            'category' => $product->category
        ]);
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
    public function update(ProductRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            // Lấy sản phẩm từ DB
            $product = Product::findOrFail($id);

            // Kiểm tra giá bán so với giá nhập
            if (isset($data['purchase_prices']) && is_array($data['purchase_prices'])) {
                $purchasePrices = array_filter($data['purchase_prices'], fn($price) => !is_null($price));
                if (!empty($purchasePrices)) {
                    $minPurchasePrice = min($purchasePrices);
                    if ($data['selling_price'] < $minPurchasePrice) {
                        return back()->withErrors([
                            'selling_price' => 'Giá bán phải lớn hơn hoặc bằng giá nhập thấp nhất (' . $minPurchasePrice . ')'
                        ])->withInput();
                    }
                }
            }

            // Xử lý ảnh
            if ($data['image_input_type'] === 'file' && $request->hasFile('image_file')) {
                // Upload file mới
                $uploadedFilePath = $request->file('image_file')->store('product_images', 'public');
                $newUrl = Storage::url($uploadedFilePath);

                // Nếu ảnh mới giống ảnh cũ -> không làm gì, xóa file mới upload đi
                if ($product->image_url === $newUrl) {
                    Storage::disk('public')->delete($uploadedFilePath);
                    $data['image_url'] = $product->image_url;
                } else {
                    // Xóa ảnh cũ nếu là local
                    $this->deleteLocalImage($product->image_url);

                    // Gán ảnh mới
                    $data['image_url'] = $newUrl;
                }
            } elseif ($data['image_input_type'] === 'url' && !empty($data['image_url'])) {
                $newUrl = $data['image_url'];

                // Nếu là link Google Images thì parse lấy ảnh gốc
                if (Str::contains($newUrl, 'google.com/imgres')) {
                    parse_str(parse_url($newUrl, PHP_URL_QUERY), $query);
                    $newUrl = $query['imgurl'] ?? $newUrl;
                }

                // Nếu URL mới giống hệt URL cũ thì giữ nguyên
                if ($product->image_url === $newUrl) {
                    $data['image_url'] = $product->image_url;
                } else {
                    // Xóa ảnh cũ nếu là file local
                    $this->deleteLocalImage($product->image_url);

                    // Gán URL mới
                    $data['image_url'] = $newUrl;
                }
            } else {
                // Không có ảnh mới, giữ nguyên
                $data['image_url'] = $product->image_url;
            }


            // Xóa các field phụ không cần lưu trong CSDL
            unset($data['image_file'], $data['image_input_type']);

            // Xử lý dữ liệu
            $data['is_active'] = isset($data['is_active']) && $data['is_active'] === '1';
            $data['selling_price'] = (float) $data['selling_price'];
            $data['min_stock_level'] = $data['min_stock_level'] ? (int) $data['min_stock_level'] : null;
            $data['max_stock_level'] = $data['max_stock_level'] ? (int) $data['max_stock_level'] : null;

            // Tách supplier & giá nhập
            $supplierIds = $data['selected_supplier_ids'] ?? [];
            $purchasePrices = $data['purchase_prices'] ?? [];
            unset($data['selected_supplier_ids'], $data['purchase_prices']);

            // Cập nhật thông tin sản phẩm
            $product->update($data);

            // Cập nhật nhà cung cấp & giá nhập
            $syncData = [];
            foreach ($supplierIds as $supplierId) {
                $syncData[$supplierId] = ['purchase_price' => $purchasePrices[$supplierId] ?? null];
            }
            $product->suppliers()->sync($syncData);

            return redirect()->route('admin.products.index')->with('success', 'Cập nhật sản phẩm thành công!');
        } catch (\Exception $e) {
            Log::error('Error updating product: ' . $e->getMessage());
            return back()->with([
                'error' => 'Đã xảy ra lỗi khi cập nhật sản phẩm. Vui lòng thử lại sau.',
            ])->withInput();
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        if ($product->batchItems()->exists()) {
            return redirect()
                ->route('admin.products.index')
                ->with('error', 'Không thể xóa sản phẩm vì đã có trong phiếu nhập.')
                ->with('info', true); // 👈 flag báo lỗi
        }

        $productId = $product->id;
        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Sản phẩm đã được xóa mềm thành công.')
            ->with('info', $productId); // 👈 flag báo xóa thành công
    }


    /**
     * Display a listing of trashed products.
     */
    public function trashed()
    {
        $products = Product::onlyTrashed()->with(['category', 'unit', 'suppliers'])->get();
        $categories = Category::all();
        $units = ProductUnit::all();
        $all_suppliers = Supplier::all();
        return Inertia::render('admin/products/Trashed', [
            'products' => $products,
            'categories' => $categories,
            'units' => $units,
            'allSuppliers' => $all_suppliers,
        ]);
    }

    /**
     * Force delete the specified resource.
     */
    public function forceDelete(string $id): RedirectResponse
    {
        $product = Product::withTrashed()->findOrFail($id);

        if ($product->image_url && str_starts_with($product->image_url, '/storage/')) {
            $path = str_replace('/storage/', '', $product->image_url);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $product->suppliers()->detach();
        $product->forceDelete();

        return redirect()->route('admin.products.index', ['show_deleted' => true])
            ->with('success', 'Sản phẩm đã được xóa vĩnh viễn.');
    }

    /**
     * Restore a soft-deleted product.
     */
    public function restore(string $id): RedirectResponse
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->restore();

        return redirect()->route('admin.products.index', ['show_deleted' => true])
            ->with('success', 'Sản phẩm đã được khôi phục thành công.');
    }
    // Generate a unique SKU automatically
    protected function generateAutoSku(): string
    {
        $prefix = 'SKU';

        // Lấy SKU mới nhất
        $latestSku = Product::where('sku', 'like', "$prefix%")
            ->orderByDesc('sku')
            ->value('sku');

        // Nếu có SKU trước đó, tăng số
        if ($latestSku) {
            // Tách số sau "SKU"
            $number = intval(substr($latestSku, strlen($prefix))) + 1;
        } else {
            $number = 1;
        }

        // Trả lại SKU mới
        return $prefix . str_pad($number, 2, '0', STR_PAD_LEFT); // Ví dụ: SKU03
    }

    private function deleteLocalImage(?string $url): void
    {
        if (!$url || !str_starts_with($url, '/storage/')) {
            return;
        }

        // Nếu file là ảnh mặc định thì không xóa
        $defaultImage = '/storage/piclumen-1747750187180.png';
        if ($url === $defaultImage) {
            return;
        }

        $path = str_replace('/storage/', '', $url);
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
