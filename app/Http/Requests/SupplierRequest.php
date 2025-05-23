<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // Import Rule class

class SupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Đặt thành true để cho phép tất cả người dùng được ủy quyền thực hiện request này.
        // Trong môi trường thực tế, bạn nên kiểm tra quyền hạn của người dùng tại đây.
        // Ví dụ: return $this->user()->can('create-supplier');
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
            // name: Bắt buộc, chuỗi, tối đa 255 ký tự, và phải là duy nhất trong bảng 'suppliers'
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')],

            // contact_person: Bắt buộc, chuỗi, tối đa 255 ký tự
            'contact_person' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'contact_person')],

            // email: Bắt buộc, định dạng email hợp lệ, tối đa 255 ký tự
            'email' => ['required', 'email', 'max:255', Rule::unique('suppliers', 'email')],

            // phone: Bắt buộc, chuỗi, tối đa 20 ký tự
            'phone' => ['required', 'string', 'max:20', Rule::unique('suppliers', 'phone')],

            // address: Là tùy chọn (nullable), chuỗi
            'address' => ['nullable', 'string'],
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
            'name.required' => 'Tên nhà cung cấp là bắt buộc.',
            'name.string' => 'Tên nhà cung cấp phải là chuỗi ký tự.',
            'name.max' => 'Tên nhà cung cấp không được vượt quá :max ký tự.',
            'name.unique' => 'Tên nhà cung cấp này đã tồn tại.',

            'contact_person.required' => 'Người liên hệ là bắt buộc.',
            'contact_person.string' => 'Người liên hệ phải là chuỗi ký tự.',
            'contact_person.max' => 'Người liên hệ không được vượt quá :max ký tự.',
            'contact_person.unique' => 'Người liên hệ này đã tồn tại.',

            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email phải là định dạng email hợp lệ.',
            'email.max' => 'Email không được vượt quá :max ký tự.',
            'email.unique' => 'Email này đã tồn tại.',

            'phone.required' => 'Số điện thoại là bắt buộc.',
            'phone.string' => 'Số điện thoại phải là chuỗi ký tự.',
            'phone.max' => 'Số điện thoại không được vượt quá :max ký tự.',
            'phone.unique' => 'Số điện thoại này đã tồn tại.',

            'address.string' => 'Địa chỉ phải là chuỗi ký tự.',
        ];
    }
}