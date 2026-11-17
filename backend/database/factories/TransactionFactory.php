<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $type   = fake()->randomElement(['credit', 'debit', 'refund']);
        $amount = fake()->randomFloat(2, 10, 5000);

        return [
            'user_id'       => User::factory(),
            'trx_id'        => 'TRX' . strtoupper(Str::random(14)),
            'payment_id'    => 'PAY' . strtoupper(Str::random(14)),
            'type'          => $type,
            'amount'        => $amount,
            'charge'        => $type === 'credit' ? round($amount * 0.015, 2) : 0,
            'balance_after' => fake()->randomFloat(2, 0, 10000),
            'currency'      => 'BDT',
            'status'        => 'completed',
            'description'   => $type === 'credit' ? 'Wallet top-up via bKash' : 'Wallet debit',
            'refunded_amount' => 0,
            'bkash_response'  => null,
        ];
    }
}
