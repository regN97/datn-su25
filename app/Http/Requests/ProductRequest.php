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
                Rule::unique('products', 'barcode')->whereNotNull('barcode'),
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
            'required' => 'Trường :attribute là bắt buộc.',
            'string' => 'Trường :attribute phải là chuỗi.',
            'min' => 'Trường :attribute phải có ít nhất :min ký tự.',
            'max' => 'Trường :attribute không được vượt quá :max ký tự.',
            'numeric' => 'Trường :attribute phải là số.',
            'integer' => 'Trường :attribute phải là số nguyên.',
            'boolean' => 'Trường :attribute phải là true hoặc false.',
            'url' => 'Đường dẫn ảnh không hợp lệ.',
            'image' => 'Tệp phải là hình ảnh.',
            'array' => 'Trường :attribute phải là danh sách.',
            'exists' => 'Giá trị được chọn không hợp lệ.',
            'gte' => 'Tồn kho tối đa phải lớn hơn hoặc bằng tồn kho tối thiểu.',
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
