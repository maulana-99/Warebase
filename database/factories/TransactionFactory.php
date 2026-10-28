<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('TRX-TEST-######'),
            'type' => TransactionType::In,
            'status' => TransactionStatus::Pending,
            'transaction_date' => now()->toDateString(),
            'supplier_id' => Supplier::factory(),
            'note' => null,
            'created_by' => User::factory(),
        ];
    }
}
