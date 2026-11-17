<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Credit the user wallet atomically and record a transaction.
     *
     * @throws \Throwable
     */
    public function credit(
        User   $user,
        float  $amount,
        string $trxId,
        string $paymentId,
        array  $bkashResponse = [],
        string $description   = 'Wallet top-up',
        float  $charge        = 0.0
    ): Transaction {
        return DB::transaction(function () use ($user, $amount, $trxId, $paymentId, $bkashResponse, $description, $charge) {
            $locked = User::lockForUpdate()->find($user->id);
            $locked->wallet_balance += $amount;
            $locked->save();

            return Transaction::create([
                'user_id'        => $locked->id,
                'trx_id'         => $trxId,
                'payment_id'     => $paymentId,
                'type'           => 'credit',
                'amount'         => $amount,
                'charge'         => $charge,
                'balance_after'  => $locked->wallet_balance,
                'currency'       => 'BDT',
                'status'         => 'completed',
                'description'    => $description,
                'bkash_response' => $bkashResponse,
            ]);
        });
    }

    /**
     * Debit the user wallet atomically and record a transaction.
     *
     * @throws \Exception|\Throwable
     */
    public function debit(
        User   $user,
        float  $amount,
        string $description   = 'Wallet debit',
        array  $extra         = []
    ): Transaction {
        return DB::transaction(function () use ($user, $amount, $description, $extra) {
            $locked = User::lockForUpdate()->find($user->id);

            if ((float) $locked->wallet_balance < $amount) {
                throw new \DomainException('Insufficient wallet balance.');
            }

            $locked->wallet_balance -= $amount;
            $locked->save();

            return Transaction::create(array_merge([
                'user_id'       => $locked->id,
                'type'          => 'debit',
                'amount'        => $amount,
                'charge'        => 0,
                'balance_after' => $locked->wallet_balance,
                'currency'      => 'BDT',
                'status'        => 'completed',
                'description'   => $description,
            ], $extra));
        });
    }

    /**
     * Process a partial or full refund against an existing credit transaction.
     *
     * @throws \Throwable
     */
    public function refund(
        User        $user,
        Transaction $original,
        float       $amount,
        array       $bkashResponse = []
    ): Transaction {
        return DB::transaction(function () use ($user, $original, $amount, $bkashResponse) {
            $locked = User::lockForUpdate()->find($user->id);
            $locked->wallet_balance -= $amount;
            $locked->save();

            // Increment cumulative refunded amount on original transaction
            $original->increment('refunded_amount', $amount);
            if ((float) $original->refunded_amount >= (float) $original->amount) {
                $original->update(['status' => 'refunded']);
            }

            return Transaction::create([
                'user_id'                 => $locked->id,
                'trx_id'                  => $bkashResponse['trxID']   ?? null,
                'payment_id'              => $original->payment_id,
                'type'                    => 'refund',
                'amount'                  => $amount,
                'charge'                  => 0,
                'balance_after'           => $locked->wallet_balance,
                'currency'                => 'BDT',
                'status'                  => 'completed',
                'description'             => 'Refund: ' . ($bkashResponse['statusMessage'] ?? 'Processed'),
                'original_transaction_id' => $original->id,
                'bkash_response'          => $bkashResponse,
            ]);
        });
    }
}
