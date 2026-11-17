<?php

namespace App\Http\Controllers;

use App\Http\Requests\TopupRequest;
use App\Models\Transaction;
use App\Services\BkashService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class BkashPaymentController extends Controller
{
    public function __construct(
        private BkashService  $bkash,
        private WalletService $wallet,
    ) {}

    public function create(TopupRequest $request): JsonResponse
    {
        $user      = $request->user();
        $agreement = $user->activeAgreement;

        if (! $agreement) {
            return response()->json(['message' => __('bkash.no_agreement')], 422);
        }

        $lockKey = "bkash:payment_lock:{$user->id}";
        $locked  = Redis::set($lockKey, 1, 'EX', 60, 'NX');

        if (! $locked) {
            return response()->json(['message' => __('bkash.payment_in_progress')], 429);
        }

        try {
            $invoiceNumber = 'INV-' . strtoupper(Str::random(10));
            $agreementId   = $agreement->agreement_id;

            $resp = $this->bkash->createPaymentWithAgreement(
                agreementId:           $agreementId,
                amount:                (float) $request->amount,
                merchantInvoiceNumber: $invoiceNumber,
                payerReference:        $user->phone ?? (string) $user->id,
            );

            if (($resp['statusCode'] ?? '') !== '0000') {
                Redis::del($lockKey);
                return response()->json(['message' => $resp['statusMessage'] ?? __('bkash.payment_failed')], 422);
            }

            return response()->json(['bkash_url' => $resp['bkashURL'], 'payment_id' => $resp['paymentID']]);

        } catch (\Exception $e) {
            Log::error('Payment create error', ['error' => $e->getMessage()]);
            Redis::del($lockKey);
            return response()->json(['message' => __('bkash.service_unavailable')], 503);
        }
    }

    public function callback(Request $request): \Illuminate\Http\RedirectResponse
    {
        $paymentID   = $request->query('paymentID');
        $status      = $request->query('status');
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');

        if (in_array($status, ['cancel', 'failure'])) {
            $this->releaseLock($paymentID);
            return redirect("{$frontendUrl}/dashboard?payment={$status}");
        }

        try {
            $resp = $this->bkash->executePayment($paymentID);

            if (($resp['statusCode'] ?? '') !== '0000') {
                Log::error('Payment execute failed', $resp);
                $this->releaseLock($paymentID);
                return redirect("{$frontendUrl}/dashboard?payment=failed");
            }

            $payerRef = $resp['payerReference'] ?? null;
            $user     = \App\Models\User::where('phone', $payerRef)->orWhere('id', $payerRef)->first();

            if (! $user) {
                return redirect("{$frontendUrl}/dashboard?payment=failed&reason=user");
            }

            $this->wallet->credit(
                user: $user, amount: (float) $resp['amount'],
                trxId: $resp['trxID'], paymentId: $resp['paymentID'],
                bkashResponse: $resp, description: 'Wallet top-up via bKash',
                charge: (float) ($resp['charge'] ?? 0),
            );

            Redis::del("bkash:payment_lock:{$user->id}");
            return redirect("{$frontendUrl}/dashboard?payment=success");

        } catch (\Exception $e) {
            Log::error('Payment callback exception', ['error' => $e->getMessage()]);
            return redirect("{$frontendUrl}/dashboard?payment=failed&reason=exception");
        }
    }

    private function releaseLock(string $paymentId): void
    {
        $txn = Transaction::where('payment_id', $paymentId)->first();
        if ($txn) Redis::del("bkash:payment_lock:{$txn->user_id}");
    }
}
