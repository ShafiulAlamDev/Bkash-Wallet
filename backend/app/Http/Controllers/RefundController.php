<?php

namespace App\Http\Controllers;

use App\Http\Requests\RefundRequest;
use App\Models\Transaction;
use App\Services\BkashService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RefundController extends Controller
{
    public function __construct(
        private BkashService  $bkash,
        private WalletService $wallet,
    ) {}

    /**
     * POST /api/transactions/{transaction}/refund
     * Partial refund using original trxId + paymentId.
     */
    public function refund(RefundRequest $request, Transaction $transaction): JsonResponse
    {
        $user = $request->user();

        if ($transaction->user_id !== $user->id) {
            return response()->json(['message' => __('bkash.unauthorized')], 403);
        }

        if (! $transaction->isRefundable()) {
            return response()->json(['message' => __('bkash.not_refundable')], 422);
        }

        $amount = (float) $request->amount;
        $reason = $request->input('reason', 'Customer request');

        try {
            $refundResp = $this->bkash->refund(
                trxId:     $transaction->trx_id,
                paymentId: $transaction->payment_id,
                amount:    $amount,
                reason:    $reason,
            );

            if (($refundResp['statusCode'] ?? '') !== '0000') {
                Log::error('Refund API failed', $refundResp);
                return response()->json([
                    'message' => $refundResp['statusMessage'] ?? __('bkash.refund_failed'),
                ], 422);
            }

            $refundTxn = $this->wallet->refund(
                user:          $user,
                original:      $transaction,
                amount:        $amount,
                bkashResponse: $refundResp,
            );

            return response()->json([
                'message'      => __('bkash.refund_success'),
                'refunded'     => $amount,
                'bkash_trx_id' => $refundResp['trxID'] ?? null,
                'transaction'  => $refundTxn,
            ]);

        } catch (\Exception $e) {
            Log::error('Refund exception', ['error' => $e->getMessage()]);
            return response()->json(['message' => __('bkash.service_unavailable')], 503);
        }
    }
}
