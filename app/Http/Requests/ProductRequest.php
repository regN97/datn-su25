<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:255',
            'barcode' => [
                'nullable',
                'string',
                'min:5',
                'max:255',
                Rule::unique('products', 'barcode')
                    ->ignore($this->route('product'), 'id')
                    ->whereNotNull('barcode'),
            ],
            'description' => 'nullable|string|max:5000',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:product_units,id',
            'selling_price' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
            'min_stock_level' => 'nullable|integer|min:0',
            'max_stock_level' => 'nullable|integer|min:0|gte:min_stock_level',
            'image_input_type' => 'required|in:file,url',
            'image_url' => Rule::requiredIf($this->input('image_input_type') === 'url') . '|nullable|string|url|max:2048',
            'image_file' => Rule::requiredIf($this->input('image_input_type') === 'file') . '|nullable|image|max:2048',
            'selected_supplier_ids' => 'required|array|min:1',
            'selected_supplier_ids.*' => 'exists:suppliers,id',
            'purchase_prices' => 'required|array',
            'purchase_prices.*' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'name.min' => 'Tên sản phẩm phải có ít nhất :min ký tự.',
            'name.max' => 'Tên sản phẩm không được vượt quá :max ký tự.',

            'barcode.unique' => 'Mã vạch đã tồn tại trong hệ thống.',
            'barcode.min' => 'Mã vạch phải có ít nhất :min ký tự.',
            'barcode.max' => 'Mã vạch không được vượt quá :max ký tự.',

            'category_id.required' => 'Vui lòng chọn danh mục.',
            'category_id.exists' => 'Danh mục được chọn không hợp lệ.',

            'unit_id.required' => 'Vui lòng chọn đơn vị tính.',
            'unit_id.exists' => 'Đơn vị tính không hợp lệ.',

            'selling_price.required' => 'Vui lòng nhập giá bán.',
            'selling_price.numeric' => 'Giá bán phải là số.',
            'selling_price.min' => 'Giá bán không được nhỏ hơn :min.',

            'is_active.required' => 'Vui lòng chọn trạng thái sản phẩm.',
            'is_active.boolean' => 'Trạng thái không hợp lệ.',

            'min_stock_level.integer' => 'Tồn kho tối thiểu phải là số nguyên.',
            'min_stock_level.min' => 'Tồn kho tối thiểu không được nhỏ hơn :min.',

            'max_stock_level.integer' => 'Tồn kho tối đa phải là số nguyên.',
            'max_stock_level.min' => 'Tồn kho tối đa không được nhỏ hơn :min.',
            'max_stock_level.gte' => 'Tồn kho tối đa phải lớn hơn hoặc bằng tồn kho tối thiểu.',

            'image_input_type.required' => 'Vui lòng chọn kiểu nhập ảnh.',
            'image_input_type.in' => 'Kiểu nhập ảnh không hợp lệ.',

            'image_url.required' => 'Vui lòng nhập đường dẫn ảnh.',
            'image_url.url' => 'Đường dẫn ảnh không hợp lệ.',
            'image_url.max' => 'Đường dẫn ảnh không được vượt quá :max ký tự.',

            'image_file.required' => 'Vui lòng tải lên ảnh sản phẩm.',
            'image_file.image' => 'Tệp tải lên phải là hình ảnh.',
            'image_file.max' => 'Ảnh tải lên không được vượt quá :max KB.',

            'selected_supplier_ids.required' => 'Vui lòng chọn ít nhất một nhà cung cấp.',
            'selected_supplier_ids.array' => 'Danh sách nhà cung cấp không hợp lệ.',
            'selected_supplier_ids.min' => 'Phải chọn ít nhất một nhà cung cấp.',
            'selected_supplier_ids.*.exists' => 'Nhà cung cấp được chọn không hợp lệ.',

            'purchase_prices.required' => 'Vui lòng nhập giá nhập cho các nhà cung cấp.',
            'purchase_prices.array' => 'Danh sách giá nhập không hợp lệ.',
            'purchase_prices.*.numeric' => 'Giá nhập phải là số.',
            'purchase_prices.*.min' => 'Giá nhập không được nhỏ hơn :min.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'tên sản phẩm',
            'barcode' => 'mã vạch',
            'description' => 'mô tả',
            'category_id' => 'danh mục',
            'unit_id' => 'đơn vị tính',
            'selling_price' => 'giá bán',
            'min_stock_level' => 'tồn kho tối thiểu',
            'max_stock_level' => 'tồn kho tối đa',
            'is_active' => 'trạng thái',
            'image_url' => 'đường dẫn ảnh',
            'image_file' => 'ảnh sản phẩm',
            'image_input_type' => 'kiểu nhập ảnh',
            'selected_supplier_ids' => 'nhà cung cấp',
            'purchase_prices' => 'giá nhập',
        ];
    }
}
