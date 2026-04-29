<?php

namespace App\Http\Requests;

// use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePembelianRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'gudang_id' => ['required', 'exists:master_gudang,id'],
            'tanggal' => ['required', 'date'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.barang_id' => ['required', 'exists:master_barang,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.harga' => ['required', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Supplier wajib dipilih.',
            'gudang_id.required' => 'Gudang tujuan wajib dipilih.',
            'items.required' => 'Minimal harus ada 1 barang pembelian.',
            'items.*.barang_id.required' => 'Barang wajib dipilih.',
            'items.*.qty.required' => 'Qty wajib diisi.',
            'items.*.harga.required' => 'Harga wajib diisi.',
        ];
    }
}
