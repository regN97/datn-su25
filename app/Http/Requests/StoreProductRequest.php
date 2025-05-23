<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'image_input_type' => ['required', 'in:url,file'],
            'image_url' => [
                Rule::requiredIf($this->input('image_input_type') === 'url'),
                'nullable',
                'url',
                'max:2048' // Max 2MB URL length
            ],
            'image_file' => [
                Rule::requiredIf($this->input('image_input_type') === 'file'),
                'nullable',
                'image',
                'mimes:jpeg,png,gif,webp',
                'max:2048', // Max 2MB file size
            ],
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
            'selected_supplier_ids' => ['required', 'array', 'min:1'],
            'selected_supplier_ids.*' => ['integer', 'exists:suppliers,id'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'barcode' => ['nullable', 'string', 'max:255', 'unique:products,barcode'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'unit_id' => ['nullable', 'integer', 'exists:product_units,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'min_stock_level' => ['required', 'integer', 'min:0'],
            'max_stock_level' => ['nullable', 'integer', 'min:0', 'gte:min_stock_level'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên sản phẩm là bắt buộc.',
            'name.max' => 'Tên sản phẩm không được quá :max ký tự.',
            'image_input_type.required' => 'Loại ảnh là bắt buộc.',
            'image_input_type.in' => 'Loại ảnh không hợp lệ.',
            'image_url.required' => 'Đường dẫn ảnh là bắt buộc khi chọn nhập URL.',
            'image_url.url' => 'Đường dẫn ảnh không hợp lệ.',
            'image_file.required' => 'Vui lòng tải lên một tệp ảnh.',
            'image_file.image' => 'Tệp phải là một hình ảnh.',
            'image_file.mimes' => 'Định dạng ảnh không hợp lệ. Chỉ chấp nhận JPEG, PNG, GIF, WEBP.',
            'image_file.max' => 'Kích thước ảnh tối đa là 2MB.',
            'sku.required' => 'Mã SKU là bắt buộc.',
            'sku.unique' => 'Mã SKU đã tồn tại.',
            'selected_supplier_ids.required' => 'Bạn phải chọn ít nhất một nhà cung cấp.',
            'selected_supplier_ids.array' => 'Dữ liệu nhà cung cấp không hợp lệ.',
            'selected_supplier_ids.min' => 'Bạn phải chọn ít nhất :min nhà cung cấp.',
            'selected_supplier_ids.*.integer' => 'ID nhà cung cấp không hợp lệ.',
            'selected_supplier_ids.*.exists' => 'Nhà cung cấp đã chọn không tồn tại.',
            'purchase_price.required' => 'Giá nhập là bắt buộc.',
            'purchase_price.numeric' => 'Giá nhập phải là số.',
            'purchase_price.min' => 'Giá nhập không được âm.',
            'is_active.required' => 'Trạng thái là bắt buộc.',
            'is_active.boolean' => 'Trạng thái không hợp lệ.',
            'selling_price.required' => 'Giá bán là bắt buộc.',
            'selling_price.numeric' => 'Giá bán phải là số.',
            'selling_price.min' => 'Giá bán không được âm.',
            'barcode.unique' => 'Mã vạch đã tồn tại.',
            'barcode.max' => 'Mã vạch không được quá :max ký tự.',
            'category_id.exists' => 'Danh mục đã chọn không tồn tại.',
            'unit_id.exists' => 'Đơn vị tính đã chọn không tồn tại.',
            'description.max' => 'Mô tả không được quá :max ký tự.',
            'min_stock_level.required' => 'Tồn kho tối thiểu là bắt buộc.',
            'min_stock_level.integer' => 'Tồn kho tối thiểu phải là số nguyên.',
            'min_stock_level.min' => 'Tồn kho tối thiểu không được âm.',
            'max_stock_level.integer' => 'Tồn kho tối đa phải là số nguyên.',
            'max_stock_level.min' => 'Tồn kho tối đa không được âm.',
            'max_stock_level.gte' => 'Tồn kho tối đa không được nhỏ hơn tồn kho tối thiểu.',
        ];
    }
}