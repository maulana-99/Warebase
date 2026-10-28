<?php

namespace App\Http\Requests\Transactions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for a stock adjustment (physical count). Only admins reach this (route middleware).
 */
class AdjustmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // When the count form was opened. Movements after it make the count stale (checked in StockService).
            'counted_since' => ['required', 'date_format:Y-m-d H:i:s', 'before_or_equal:now'],
            // A reason is mandatory: every correction must be explainable later.
            'note' => ['required', 'string', 'max:1000'],
            // A stock opname may cover the whole warehouse in one go.
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.counted' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'note' => 'alasan',
            'counted_since' => 'waktu mulai hitung',
            'items.*.product_id' => 'produk',
            'items.*.counted' => 'stok fisik',
        ];
    }
}
