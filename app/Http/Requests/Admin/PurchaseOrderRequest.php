<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseOrderRequest extends FormRequest
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
        $purchaseOrderId = $this->route('purchase_order');
        
        return [
            'po_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('purchase_orders', 'po_number')->ignore($purchaseOrderId),
            ],
            'supplier_id' => 'required|exists:suppliers,id',
            'status_id' => 'required|exists:po_statuses,id',
            'order_date' => 'required|date|before_or_equal:today',
            'expected_delivery_date' => [
                'nullable',
                'date',
                'after:order_date',
            ],
            'actual_delivery_date' => [
                'nullable',
                'date',
                'after_or_equal:order_date',
            ],
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => [
                'nullable',
                'required_with:discount_amount',
                'in:percent,amount'
            ],
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.ordered_quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.discount_type' => [
                'nullable',
                'required_with:items.*.discount_amount',
                'in:percent,amount'
            ],
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'po_number.required' => 'Số đơn đặt hàng là bắt buộc.',
            'po_number.string' => 'Số đơn đặt hàng phải là chuỗi ký tự.',
            'po_number.max' => 'Số đơn đặt hàng không được vượt quá :max ký tự.',
            'po_number.unique' => 'Số đơn đặt hàng này đã tồn tại.',
            
            'supplier_id.required' => 'Nhà cung cấp là bắt buộc.',
            'supplier_id.exists' => 'Nhà cung cấp được chọn không tồn tại.',
            
            'status_id.required' => 'Trạng thái đơn hàng là bắt buộc.',
            'status_id.exists' => 'Trạng thái được chọn không tồn tại.',
            
            'order_date.required' => 'Ngày đặt hàng là bắt buộc.',
            'order_date.date' => 'Ngày đặt hàng phải là định dạng ngày hợp lệ.',
            'order_date.before_or_equal' => 'Ngày đặt hàng không thể là ngày trong tương lai.',
            
            'expected_delivery_date.date' => 'Ngày giao hàng dự kiến phải là định dạng ngày hợp lệ.',
            'expected_delivery_date.after' => 'Ngày giao hàng dự kiến phải sau ngày đặt hàng.',
            
            'actual_delivery_date.date' => 'Ngày giao hàng thực tế phải là định dạng ngày hợp lệ.',
            'actual_delivery_date.after_or_equal' => 'Ngày giao hàng thực tế phải sau hoặc bằng ngày đặt hàng.',
            
            'discount_amount.numeric' => 'Số tiền giảm giá phải là số.',
            'discount_amount.min' => 'Số tiền giảm giá phải lớn hơn hoặc bằng 0.',
            
            'discount_type.required_with' => 'Loại giảm giá là bắt buộc khi có số tiền giảm giá.',
            'discount_type.in' => 'Loại giảm giá không hợp lệ.',
            
            'total_amount.required' => 'Tổng tiền là bắt buộc.',
            'total_amount.numeric' => 'Tổng tiền phải là số.',
            'total_amount.min' => 'Tổng tiền phải lớn hơn 0.',
            
            'notes.max' => 'Ghi chú không được vượt quá :max ký tự.',
            
            'items.required' => 'Danh sách sản phẩm là bắt buộc.',
            'items.array' => 'Danh sách sản phẩm phải là danh sách.',
            'items.min' => 'Phải có ít nhất :min sản phẩm.',
            
            'items.*.product_id.required' => 'Sản phẩm là bắt buộc.',
            'items.*.product_id.exists' => 'Sản phẩm được chọn không tồn tại.',
            
            'items.*.ordered_quantity.required' => 'Số lượng đặt hàng là bắt buộc.',
            'items.*.ordered_quantity.integer' => 'Số lượng đặt hàng phải là số nguyên.',
            'items.*.ordered_quantity.min' => 'Số lượng đặt hàng phải lớn hơn 0.',
            
            'items.*.unit_cost.required' => 'Đơn giá là bắt buộc.',
            'items.*.unit_cost.numeric' => 'Đơn giá phải là số.',
            'items.*.unit_cost.min' => 'Đơn giá phải lớn hơn hoặc bằng 0.',
            
            'items.*.subtotal.required' => 'Thành tiền là bắt buộc.',
            'items.*.subtotal.numeric' => 'Thành tiền phải là số.',
            'items.*.subtotal.min' => 'Thành tiền phải lớn hơn hoặc bằng 0.',
            
            'items.*.discount_amount.numeric' => 'Số tiền giảm giá phải là số.',
            'items.*.discount_amount.min' => 'Số tiền giảm giá phải lớn hơn hoặc bằng 0.',
            
            'items.*.discount_type.required_with' => 'Loại giảm giá là bắt buộc khi có số tiền giảm giá.',
            'items.*.discount_type.in' => 'Loại giảm giá không hợp lệ.',
            
            'items.*.notes.max' => 'Ghi chú không được vượt quá :max ký tự.',
        ];
    }

    public function attributes(): array
    {
        return [
            'po_number' => 'số đơn đặt hàng',
            'supplier_id' => 'nhà cung cấp',
            'status_id' => 'trạng thái đơn hàng',
            'order_date' => 'ngày đặt hàng',
            'expected_delivery_date' => 'ngày giao hàng dự kiến',
            'actual_delivery_date' => 'ngày giao hàng thực tế',
            'discount_amount' => 'số tiền giảm giá',
            'discount_type' => 'loại giảm giá',
            'total_amount' => 'tổng tiền',
            'notes' => 'ghi chú',
            'items' => 'danh sách sản phẩm',
            'items.*.product_id' => 'sản phẩm',
            'items.*.ordered_quantity' => 'số lượng đặt hàng',
            'items.*.unit_cost' => 'đơn giá',
            'items.*.subtotal' => 'thành tiền',
            'items.*.discount_amount' => 'số tiền giảm giá',
            'items.*.discount_type' => 'loại giảm giá',
            'items.*.notes' => 'ghi chú',
        ];
    }
}
