<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|string|max:30',
            'grass_amount' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'Order harus dipilih',
            'order_id.exists' => 'Order tidak ditemukan',
            'payment_method.required' => 'Metode pembayaran harus diisi',
            'grass_amount.required' => 'Jumlah pembayaran harus diisi',
            'grass_amount.min' => 'Jumlah pembayaran tidak boleh negatif',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors' => $validator->errors()
        ], 422));
    }
}
