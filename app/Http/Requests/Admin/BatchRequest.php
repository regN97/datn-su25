<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BatchRequest extends FormRequest
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
            'batch_items' => 'required|array',
            'batch_items.*.product_id' => 'required|integer|exists:products,id',
            'batch_items.*.purchase_order_item_id' => 'nullable|integer|exists:purchase_order_items,id',
            'batch_items.*.received_quantity' => 'required|integer|min:0',
            'batch_items.*.rejected_quantity' => 'nullable|integer|min:0',
            'batch_items.*.purchase_price' => 'required|numeric|min:0',
            'batch_items.*.total_amount' => 'required|numeric|min:0',
            'purchase_order_id' => 'nullable|integer|exists:purchase_orders,id',
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'total_amount' => 'required|numeric|min:0',
            'discount.type' => 'nullable|in:amount,percent',
            'discount.value' => 'nullable|numeric|min:0',
            'payment_status' => 'required_if:paid,partially_paid,unpaid|nullable|in:paid,partially_paid,unpaid',
            'payment_method' => 'required_if:payment_status,paid,partially_paid|nullable|in:cash,bank_transfer,credit_card',
            'payment_date' => 'required_if:payment_status,paid,partially_paid|nullable|date',
            'paid_amount' => 'required_if:payment_status,paid,partially_paid|numeric|min:0',
            'import_date' => 'required|date',
            'batch_code' => 'nullable|string|max:50',
            'invoice_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'user_id' => 'nullable|integer|exists:users,id'
        ];
    }

    public function messages(): array
    {
        return [
            'batch_items.required' => 'Danh sách sản phẩm nhập hàng là bắt buộc.',
            'batch_items.array' => 'Danh sách sản phẩm nhập hàng phải là danh sách.',
            
            'batch_items.*.product_id.required' => 'Sản phẩm là bắt buộc.',
            'batch_items.*.product_id.integer' => 'ID sản phẩm phải là số nguyên.',
            'batch_items.*.product_id.exists' => 'Sản phẩm không tồn tại.',
            
            'batch_items.*.purchase_order_item_id.integer' => 'ID đơn hàng phải là số nguyên.',
            'batch_items.*.purchase_order_item_id.exists' => 'Đơn hàng không tồn tại.',
            
            'batch_items.*.received_quantity.required' => 'Số lượng nhận là bắt buộc.',
            'batch_items.*.received_quantity.integer' => 'Số lượng nhận phải là số nguyên.',
            'batch_items.*.received_quantity.min' => 'Số lượng nhận phải lớn hơn hoặc bằng 0.',
            
            'batch_items.*.rejected_quantity.integer' => 'Số lượng từ chối phải là số nguyên.',
            'batch_items.*.rejected_quantity.min' => 'Số lượng từ chối phải lớn hơn hoặc bằng 0.',
            
            'batch_items.*.purchase_price.required' => 'Giá nhập là bắt buộc.',
            'batch_items.*.purchase_price.numeric' => 'Giá nhập phải là số.',
            'batch_items.*.purchase_price.min' => 'Giá nhập phải lớn hơn hoặc bằng 0.',
            
            'batch_items.*.total_amount.required' => 'Thành tiền là bắt buộc.',
            'batch_items.*.total_amount.numeric' => 'Thành tiền phải là số.',
            'batch_items.*.total_amount.min' => 'Thành tiền phải lớn hơn hoặc bằng 0.',
            
            'purchase_order_id.required' => 'Đơn đặt hàng là bắt buộc.',
            'purchase_order_id.integer' => 'ID đơn đặt hàng phải là số nguyên.',
            'purchase_order_id.exists' => 'Đơn đặt hàng không tồn tại.',
            
            'supplier_id.required' => 'Nhà cung cấp là bắt buộc.',
            'supplier_id.integer' => 'ID nhà cung cấp phải là số nguyên.',
            'supplier_id.exists' => 'Nhà cung cấp không tồn tại.',
            
            'total_amount.required' => 'Tổng tiền là bắt buộc.',
            'total_amount.numeric' => 'Tổng tiền phải là số.',
            'total_amount.min' => 'Tổng tiền phải lớn hơn hoặc bằng 0.',
            
            'discount.type.in' => 'Loại giảm giá không hợp lệ.',
            'discount.value.numeric' => 'Giá trị giảm giá phải là số.',
            'discount.value.min' => 'Giá trị giảm giá phải lớn hơn hoặc bằng 0.',
            
            'payment_status.required' => 'Trạng thái thanh toán là bắt buộc.',
            'payment_status.in' => 'Trạng thái thanh toán không hợp lệ.',
            
            'payment_method.required_if' => 'Phương thức thanh toán là bắt buộc khi đã thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
            
            'payment_date.required_if' => 'Ngày thanh toán là bắt buộc khi đã thanh toán.',
            'payment_date.date' => 'Ngày thanh toán phải là định dạng ngày hợp lệ.',
            
            'paid_amount.required_if' => 'Số tiền đã thanh toán là bắt buộc khi đã thanh toán.',
            'paid_amount.numeric' => 'Số tiền đã thanh toán phải là số.',
            'paid_amount.min' => 'Số tiền đã thanh toán phải lớn hơn hoặc bằng 0.',
            
            'expected_import_date.required' => 'Ngày nhập hàng dự kiến là bắt buộc.',
            'expected_import_date.date' => 'Ngày nhập hàng dự kiến phải là định dạng ngày hợp lệ.',
            
            'batch_code.max' => 'Mã lô hàng không được vượt quá :max ký tự.',
            'invoice_code.max' => 'Mã hóa đơn không được vượt quá :max ký tự.',
            
            'user_id.integer' => 'ID người dùng phải là số nguyên.',
            'user_id.exists' => 'Người dùng không tồn tại.',
        ];
    }

    public function attributes(): array
    {
        return [
            'batch_items' => 'danh sách sản phẩm nhập hàng',
            'batch_items.*.product_id' => 'sản phẩm',
            'batch_items.*.purchase_order_item_id' => 'đơn hàng',
            'batch_items.*.received_quantity' => 'số lượng nhận',
            'batch_items.*.rejected_quantity' => 'số lượng từ chối',
            'batch_items.*.purchase_price' => 'giá nhập',
            'batch_items.*.total_amount' => 'thành tiền',
            'purchase_order_id' => 'đơn đặt hàng',
            'supplier_id' => 'nhà cung cấp',
            'total_amount' => 'tổng tiền',
            'discount.type' => 'loại giảm giá',
            'discount.value' => 'giá trị giảm giá',
            'payment_status' => 'trạng thái thanh toán',
            'payment_method' => 'phương thức thanh toán',
            'payment_date' => 'ngày thanh toán',
            'paid_amount' => 'số tiền đã thanh toán',
            'expected_import_date' => 'ngày nhập hàng dự kiến',
            'batch_code' => 'mã lô hàng',
            'invoice_code' => 'mã hóa đơn',
            'notes' => 'ghi chú',
            'user_id' => 'người dùng',
        ];
    }
}
