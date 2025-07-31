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
            'purchase_order_id' => 'required|integer|exists:purchase_orders,id',
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'total_amount' => 'required|numeric|min:0',
            'discount.type' => 'nullable|in:amount,percent',
            'discount.value' => 'nullable|numeric|min:0',
            'payment_status' => 'required|in:paid,partially_paid,unpaid',
            'payment_method' => 'required_if:payment_status,paid,partially_paid|nullable|in:cash,bank_transfer,credit_card',
            'payment_date' => 'required_if:payment_status,paid,partially_paid|nullable|date',
            'paid_amount' => 'required_if:payment_status,paid,partially_paid|numeric|min:0',
            'expected_import_date' => 'required|date',
            'batch_code' => 'nullable|string|max:50',
            'invoice_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'user_id' => 'nullable|integer|exists:users,id'
        ];
    }
}
