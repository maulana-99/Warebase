<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Describes and validates the four master-data tables. Only admins reach this (route middleware).
 *
 * Per table: UI texts, searchable columns, the relation that blocks deletion, the form fields and their rules.
 * Names and codes are unique across soft-deleted rows too, because the database index is.
 */
class MasterDataRequest extends FormRequest
{
    public const TYPES = [
        'categories' => [
            'title' => 'Kategori',
            'singular' => 'Kategori',
            'order' => 'name',
            'search' => ['name', 'description'],
            'used_by' => 'products',
            'used_label' => 'produk',
            'columns' => ['name' => 'Nama', 'description' => 'Deskripsi'],
            'fields' => [
                'name' => ['label' => 'Nama kategori', 'max' => 100, 'required' => true, 'unique' => true],
                'description' => ['label' => 'Deskripsi', 'max' => 1000, 'textarea' => true],
            ],
        ],
        'locations' => [
            'title' => 'Rak',
            'singular' => 'Rak',
            'order' => 'code',
            'search' => ['code', 'name', 'description'],
            'used_by' => 'products',
            'used_label' => 'produk',
            'columns' => ['code' => 'Kode', 'name' => 'Nama', 'description' => 'Deskripsi'],
            'fields' => [
                'code' => ['label' => 'Kode rak', 'max' => 20, 'required' => true, 'unique' => true, 'hint' => 'Contoh: A-01. Otomatis huruf besar.'],
                'name' => ['label' => 'Nama rak', 'max' => 100, 'required' => true],
                'description' => ['label' => 'Deskripsi', 'max' => 1000, 'textarea' => true],
            ],
        ],
        'suppliers' => [
            'title' => 'Supplier',
            'singular' => 'Supplier',
            'order' => 'name',
            'search' => ['name', 'phone', 'email', 'address'],
            'used_by' => 'transactions',
            'used_label' => 'transaksi',
            'columns' => ['name' => 'Nama', 'phone' => 'Telepon', 'email' => 'Email'],
            'fields' => [
                'name' => ['label' => 'Nama supplier', 'max' => 150, 'required' => true],
                'phone' => ['label' => 'Telepon', 'max' => 30],
                'email' => ['label' => 'Email', 'max' => 255, 'email' => true],
                'address' => ['label' => 'Alamat', 'max' => 1000, 'textarea' => true],
            ],
        ],
        'departments' => [
            'title' => 'Divisi',
            'singular' => 'Divisi',
            'order' => 'name',
            'search' => ['name', 'description'],
            'used_by' => 'transactions',
            'used_label' => 'transaksi',
            'columns' => ['name' => 'Nama', 'description' => 'Deskripsi'],
            'fields' => [
                'name' => ['label' => 'Nama divisi', 'max' => 150, 'required' => true, 'unique' => true],
                'description' => ['label' => 'Deskripsi', 'max' => 1000, 'textarea' => true],
            ],
        ],
    ];

    private const TABLES = ['categories' => 'categories', 'locations' => 'locations', 'suppliers' => 'suppliers', 'departments' => 'departments'];

    /** @return array<string, mixed> */
    public function config(): array
    {
        return self::TYPES[$this->route('type')];
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('type') === 'locations' && is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper(trim($this->input('code')))]);
        }
    }

    public function rules(): array
    {
        $type = $this->route('type');
        $rules = [];

        foreach ($this->config()['fields'] as $name => $field) {
            $rule = [$field['required'] ?? false ? 'required' : 'nullable', 'string', 'max:'.$field['max']];

            if ($field['email'] ?? false) {
                $rule[] = 'email';
            }
            if ($field['unique'] ?? false) {
                $rule[] = Rule::unique(self::TABLES[$type], $name)->ignore($this->route('id'));
            }

            $rules[$name] = $rule;
        }

        return $rules;
    }
}
