<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderItemRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'product_id' => 'required|exists:products,id',
            'product_name' => 'required|string|max:100',
            'product_sku' => 'required|string|max:100',
            'ordered_quantity' => 'required|integer|min:1',
            'received_quantity' => 'nullable|integer|min:0',
            'quantity_returned' => 'nullable|integer|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => [
                'nullable',
                'required_with:discount_amount',
                'in:percent,amount'
            ],
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'purchase_order_id.required' => 'Đơn đặt hàng là bắt buộc.',
            'purchase_order_id.exists' => 'Đơn đặt hàng không tồn tại.',
            
            'product_id.required' => 'Sản phẩm là bắt buộc.',
            'product_id.exists' => 'Sản phẩm không tồn tại.',
            
            'product_name.required' => 'Tên sản phẩm là bắt buộc.',
            'product_name.string' => 'Tên sản phẩm phải là chuỗi ký tự.',
            'product_name.max' => 'Tên sản phẩm không được vượt quá :max ký tự.',
            
            'product_sku.required' => 'SKU sản phẩm là bắt buộc.',
            'product_sku.string' => 'SKU sản phẩm phải là chuỗi ký tự.',
            'product_sku.max' => 'SKU sản phẩm không được vượt quá :max ký tự.',
            
            'ordered_quantity.required' => 'Số lượng đặt hàng là bắt buộc.',
            'ordered_quantity.integer' => 'Số lượng đặt hàng phải là số nguyên.',
            'ordered_quantity.min' => 'Số lượng đặt hàng phải lớn hơn 0.',
            
            'received_quantity.integer' => 'Số lượng đã nhận phải là số nguyên.',
            'received_quantity.min' => 'Số lượng đã nhận phải lớn hơn hoặc bằng 0.',
            
            'quantity_returned.integer' => 'Số lượng trả lại phải là số nguyên.',
            'quantity_returned.min' => 'Số lượng trả lại phải lớn hơn hoặc bằng 0.',
            
            'unit_cost.required' => 'Đơn giá là bắt buộc.',
            'unit_cost.numeric' => 'Đơn giá phải là số.',
            'unit_cost.min' => 'Đơn giá phải lớn hơn hoặc bằng 0.',
            
            'subtotal.required' => 'Thành tiền là bắt buộc.',
            'subtotal.numeric' => 'Thành tiền phải là số.',
            'subtotal.min' => 'Thành tiền phải lớn hơn hoặc bằng 0.',
            
            'discount_amount.numeric' => 'Số tiền giảm giá phải là số.',
            'discount_amount.min' => 'Số tiền giảm giá phải lớn hơn hoặc bằng 0.',
            
            'discount_type.required_with' => 'Loại giảm giá là bắt buộc khi có số tiền giảm giá.',
            'discount_type.in' => 'Loại giảm giá không hợp lệ.',
            
            'notes.max' => 'Ghi chú không được vượt quá :max ký tự.',
        ];
    }

    public function attributes(): array
    {
        return [
            'purchase_order_id' => 'đơn đặt hàng',
            'product_id' => 'sản phẩm',
            'product_name' => 'tên sản phẩm',
            'product_sku' => 'SKU sản phẩm',
            'ordered_quantity' => 'số lượng đặt hàng',
            'received_quantity' => 'số lượng đã nhận',
            'quantity_returned' => 'số lượng trả lại',
            'unit_cost' => 'đơn giá',
            'subtotal' => 'thành tiền',
            'discount_amount' => 'số tiền giảm giá',
            'discount_type' => 'loại giảm giá',
            'notes' => 'ghi chú',
        ];
    }
}
