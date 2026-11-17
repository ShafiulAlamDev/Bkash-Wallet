<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo user (fixed credentials for development)
        $demo = User::firstOrCreate(
            ['email' => 'demo@bkash.test'],
            [
                'name'             => 'Demo User',
                'phone'            => '01712345678',
                'password'         => Hash::make('password'),
                'wallet_balance'   => 1500.00,
                'preferred_locale' => 'en',
            ]
        );

        // Seed sample transactions for demo user
        if ($demo->transactions()->count() === 0) {
            $balance = 0;

            $samples = [
                ['type' => 'credit', 'amount' => 1000, 'description' => 'Wallet top-up via bKash'],
                ['type' => 'credit', 'amount' => 500,  'description' => 'Wallet top-up via bKash'],
                ['type' => 'debit',  'amount' => 200,  'description' => 'Purchase - Shajgoj'],
                ['type' => 'refund', 'amount' => 50,   'description' => 'Partial refund'],
                ['type' => 'credit', 'amount' => 250,  'description' => 'Wallet top-up via bKash'],
                ['type' => 'debit',  'amount' => 100,  'description' => 'Food delivery - Pathao'],
            ];

            foreach ($samples as $s) {
                $balance += $s['type'] === 'credit' ? $s['amount'] : -$s['amount'];

                Transaction::create([
                    'user_id'         => $demo->id,
                    'trx_id'          => 'TRX' . strtoupper(substr(md5(uniqid()), 0, 14)),
                    'payment_id'      => 'PAY' . strtoupper(substr(md5(uniqid()), 0, 14)),
                    'type'            => $s['type'],
                    'amount'          => $s['amount'],
                    'charge'          => $s['type'] === 'credit' ? round($s['amount'] * 0.015, 2) : 0,
                    'balance_after'   => $balance,
                    'currency'        => 'BDT',
                    'status'          => 'completed',
                    'description'     => $s['description'],
                    'refunded_amount' => 0,
                ]);
            }
        }

        // Extra random users for testing
        User::factory(5)->create();

        $this->command->info('✅ Demo: demo@bkash.test / password');
    }
}
