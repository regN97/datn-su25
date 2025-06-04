<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();
        // dd($categories);
        return Inertia::render('admin/categories/Index')->with([
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all();
        return Inertia::render('admin/categories/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name'),
            ],
            'parent_id' => [
                'nullable', // Cho phép null nếu là danh mục gốc
                'integer',
                Rule::exists('categories', 'id'),
            ],
            'description' => 'nullable|string|max:5000',
        ];

        $messages = [
            'name.required' => 'Tên danh mục là bắt buộc.',
            'name.unique' => 'Tên danh mục đã tồn tại.',
            'parent_id.integer' => 'Danh mục cha không hợp lệ.',
            'parent_id.exists' => 'Danh mục cha không tồn tại.',
        ];

        $validated = $request->validate($rules, $messages);

        Category::create($validated); // Đảm bảo model Category có $fillable hoặc $guarded = []

        return redirect()->route('admin.categories.index')->with('success', 'Thêm danh mục thành công!');
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
        $category = Category::find($id);
        $categories = Category::where('id', '!=', $id)->get();

        if (!$category) {
            return redirect()->route('admin.categories.index')->with('error', 'Danh mục không tồn tại.');
        }

        return Inertia::render('admin/categories/Edit', [
            'category' => $category,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return redirect()->route('admin.categories.index')->with('error', 'Danh mục không tồn tại.');
        }

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($id),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($id) {
                    return $query->where('id', '!=', $id);
                }),
            ],
            'description' => 'nullable|string|max:5000',
        ];

        $messages = [
            'name.required' => 'Tên danh mục là bắt buộc.',
            'name.unique' => 'Tên danh mục đã tồn tại.',
            'parent_id.integer' => 'Danh mục cha không hợp lệ.',
            'parent_id.exists' => 'Danh mục cha không tồn tại.',
        ];

        $validated = $request->validate($rules, $messages);

        $category->update($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Cập nhật danh mục thành công!');
    }

    // Trang thùng rác
    public function trashed() {
        $categories = Category::onlyTrashed()->get();
        return Inertia::render('admin/categories/Trashed')->with(['categories' => $categories] );
    }
    // Xóa mềm danh mục
    public function destroy($id)
    {
        $category = Category::find($id); // Tìm danh mục
        if ($category) { // Đảm bảo danh mục tồn tại trước khi xóa
            $category->delete(); // Thao tác này sẽ thực hiện xóa mềm do SoftDeletes
            return redirect()->route('admin.categories.index')->with('success', 'Đã xóa thành công');
        }

        return redirect()->route('admin.categories.index')->with('error', 'Không tìm thấy danh mục để xóa.');
    }
     /**
     * Restore the specified trashed resource.
     */
    public function restore(string $id)
    {
        $supplier = Category::onlyTrashed()->findOrFail($id);
        $supplier->restore();

        return redirect()->back()->with('success', 'Danh mục đã được khôi phục!');
    }

    /**
     * Permanently remove the specified resource from storage.
     */
    public function forceDelete(string $id)
    {
        $supplier = Category::onlyTrashed()->findOrFail($id);
        $supplier->forceDelete();

        return redirect()->back()->with('success', 'Danh mục đã được xóa vĩnh viễn!');
    }
}
