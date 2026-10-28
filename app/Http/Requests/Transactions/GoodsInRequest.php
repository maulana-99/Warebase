<?php

namespace App\Http\Requests\Transactions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for recording goods received from a supplier (several products at once).
 */
class GoodsInRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            // distinct: one row per product, matching the unique (transaction_id, product_id) index.
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'items.*.product_id' => 'produk',
            'items.*.quantity' => 'jumlah',
        ];
    }
}
