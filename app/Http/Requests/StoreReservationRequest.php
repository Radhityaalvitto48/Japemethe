<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'seating_type' => 'required|in:lesehan,kursi',
            'id_table' => 'required|exists:tables,id',
            'reservation_date' => 'required|date|after_or_equal:today',
            'reservation_time' => 'required|date_format:H:i',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Nama pelanggan harus diisi',
            'customer_phone.required' => 'Nomor telepon harus diisi',
            'seating_type.required' => 'Tipe tempat duduk harus dipilih',
            'seating_type.in' => 'Tipe tempat duduk tidak valid',
            'id_table.required' => 'Meja harus dipilih',
            'id_table.exists' => 'Meja tidak ditemukan',
            'reservation_date.required' => 'Tanggal reservasi harus diisi',
            'reservation_date.after_or_equal' => 'Tanggal reservasi tidak boleh di masa lalu',
            'reservation_time.required' => 'Waktu reservasi harus diisi',
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
