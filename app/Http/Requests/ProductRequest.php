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
        $productId = $this->route('product'); // Lấy ID sản phẩm nếu đang edit

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('products', 'name')->ignore($productId),
            ],
            'barcode' => [
                'nullable',
                'string',
                'min:3',
                'max:100',
                Rule::unique('products', 'barcode')->ignore($productId)->whereNotNull('barcode'),
            ],
            'description' => 'nullable|string|max:5000',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:product_units,id',
            'selling_price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'min_stock_level' => 'nullable|integer|min:0',
            'max_stock_level' => [
                'nullable',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) {
                    $minStock = $this->input('min_stock_level');
                    if ($minStock !== null && $value !== null && $value <= $minStock) {
                        $fail('Tồn kho tối đa phải lớn hơn tồn kho tối thiểu.');
                    }
                },
            ],
            'image_input_type' => 'required|in:file,url',
            'image_url' => [
                Rule::requiredIf($this->input('image_input_type') === 'url'),
                'nullable',
                'string',
                'regex:/^(\/storage\/.+|https?:\/\/.+)$/',
                'max:512'
            ],
            'image_file' => [
                Rule::requiredIf($this->input('image_input_type') === 'file'),
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,gif,webp',
                'max:2048'
            ],
            'selected_supplier_ids' => 'required|array|min:1',
            'selected_supplier_ids.*' => 'exists:suppliers,id',
            'purchase_prices' => 'required|array',
            'purchase_prices.*' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
<<<<<<< HEAD
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
=======
            'name.required' => 'Tên sản phẩm là bắt buộc.',
            'name.string' => 'Tên sản phẩm phải là chuỗi ký tự.',
            'name.min' => 'Tên sản phẩm phải có ít nhất :min ký tự.',
            'name.max' => 'Tên sản phẩm không được vượt quá :max ký tự.',
            'name.unique' => 'Tên sản phẩm này đã tồn tại.',


            'barcode.string' => 'Mã vạch phải là chuỗi ký tự.',
            'barcode.min' => 'Mã vạch phải có ít nhất :min ký tự.',
            'barcode.max' => 'Mã vạch không được vượt quá :max ký tự.',
            'barcode.unique' => 'Mã vạch này đã tồn tại.',

            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'description.max' => 'Mô tả không được vượt quá :max ký tự.',

            'category_id.required' => 'Danh mục là bắt buộc.',
            'category_id.exists' => 'Danh mục được chọn không tồn tại.',

            'unit_id.required' => 'Đơn vị tính là bắt buộc.',
            'unit_id.exists' => 'Đơn vị tính được chọn không tồn tại.',

            'selling_price.required' => 'Giá bán là bắt buộc.',
            'selling_price.numeric' => 'Giá bán phải là số.',
            'selling_price.min' => 'Giá bán phải lớn hơn hoặc bằng 0.',

            'is_active.boolean' => 'Trạng thái phải là true hoặc false.',

            'min_stock_level.integer' => 'Tồn kho tối thiểu phải là số nguyên.',
            'min_stock_level.min' => 'Tồn kho tối thiểu phải lớn hơn hoặc bằng 0.',

            'max_stock_level.integer' => 'Tồn kho tối đa phải là số nguyên.',
            'max_stock_level.min' => 'Tồn kho tối đa phải lớn hơn hoặc bằng 0.',

            'image_input_type.required' => 'Kiểu nhập ảnh là bắt buộc.',
            'image_input_type.in' => 'Kiểu nhập ảnh không hợp lệ.',

            'image_url.required' => 'Đường dẫn ảnh là bắt buộc khi chọn kiểu URL.',
            'image_url.string' => 'Đường dẫn ảnh phải là chuỗi ký tự.',
            'image_url.url' => 'Đường dẫn ảnh không hợp lệ.',
            'image_url.max' => 'Đường dẫn ảnh không được vượt quá :max ký tự.',

            'image_file.required' => 'Ảnh sản phẩm là bắt buộc khi chọn kiểu upload.',
            'image_file.image' => 'Tệp phải là hình ảnh.',
            'image_file.mimes' => 'Định dạng ảnh không được hỗ trợ. Chỉ chấp nhận: :values.',
            'image_file.max' => 'Kích thước ảnh không được vượt quá :max KB.',

            'selected_supplier_ids.required' => 'Nhà cung cấp là bắt buộc.',
            'selected_supplier_ids.array' => 'Nhà cung cấp phải là danh sách.',
            'selected_supplier_ids.min' => 'Phải chọn ít nhất :min nhà cung cấp.',
            'selected_supplier_ids.*.exists' => 'Nhà cung cấp được chọn không tồn tại.',

            'purchase_prices.required' => 'Giá nhập là bắt buộc.',
            'purchase_prices.array' => 'Giá nhập phải là danh sách.',
            'purchase_prices.*.required' => 'Giá nhập của nhà cung cấp là bắt buộc.',
            'purchase_prices.*.numeric' => 'Giá nhập phải là số.',
            'purchase_prices.*.min' => 'Giá nhập phải lớn hơn hoặc bằng 0.',
>>>>>>> ed6d46950a45c627d034e66ead998f612f968f03
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
