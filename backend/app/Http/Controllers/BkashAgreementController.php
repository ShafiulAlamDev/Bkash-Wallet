<?php

namespace App\Http\Controllers;

use App\Models\BkashAgreement;
use App\Services\BkashService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BkashAgreementController extends Controller
{
    public function __construct(private BkashService $bkash) {}

    // ─── Step 1: Create Agreement ─────────────────────────────────────────────

    /**
     * POST /api/bkash/agreement/create
     *
     * Calls bKash createAgreement, returns the redirect URL to the frontend.
     */
    public function create(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke any existing active agreement
        $user->agreements()->where('status', 'active')->update(['status' => 'cancelled']);

        $payerReference = $user->phone ?? (string) $user->id;

        try {
            $response = $this->bkash->createAgreement($payerReference);

            if (($response['statusCode'] ?? '') !== '0000') {
                return response()->json([
                    'message' => $response['statusMessage'] ?? 'Agreement creation failed',
                ], 422);
            }

            return response()->json([
                'bkash_url'  => $response['bkashURL'],
                'payment_id' => $response['paymentID'],
            ]);
        } catch (\Exception $e) {
            Log::error('Agreement create error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Service unavailable'], 503);
        }
    }

    // ─── Step 2: Handle Callback ──────────────────────────────────────────────

    /**
     * GET /api/bkash/agreement/callback
     *
     * bKash redirects here after user completes/cancels agreement flow.
     * We execute the agreement and store the encrypted agreementId.
     */
    public function callback(Request $request): \Illuminate\Http\RedirectResponse
    {
        $paymentID = $request->query('paymentID');
        $status    = $request->query('status');

        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));

        if ($status === 'cancel' || $status === 'failure') {
            Log::warning('Agreement callback status', ['status' => $status, 'paymentID' => $paymentID]);
            return redirect("{$frontendUrl}/dashboard?agreement=failed&reason={$status}");
        }

        try {
            $response = $this->bkash->executeAgreement($paymentID);

            if (($response['statusCode'] ?? '') !== '0000') {
                Log::error('Agreement execute failed', $response);
                return redirect("{$frontendUrl}/dashboard?agreement=failed&reason=execute");
            }

            // Find user by payer reference (phone or id)
            $payerRef = $response['payerReference'] ?? null;
            $user = \App\Models\User::where('phone', $payerRef)
                ->orWhere('id', $payerRef)
                ->first();

            if (! $user) {
                Log::error('Agreement callback: user not found', ['payerReference' => $payerRef]);
                return redirect("{$frontendUrl}/dashboard?agreement=failed&reason=user_not_found");
            }

            // Revoke old active agreements
            $user->agreements()->where('status', 'active')->update(['status' => 'cancelled']);

            // Store new agreement with encrypted agreementId
            $agreement = new BkashAgreement();
            $agreement->user_id         = $user->id;
            $agreement->agreement_id    = $response['agreementID']; // triggers encryption mutator
            $agreement->payer_reference = $response['payerReference'];
            $agreement->status          = 'active';
            $agreement->save();

            return redirect("{$frontendUrl}/dashboard?agreement=success");
        } catch (\Exception $e) {
            Log::error('Agreement callback exception', ['error' => $e->getMessage()]);
            return redirect("{$frontendUrl}/dashboard?agreement=failed&reason=exception");
        }
    }

    // ─── Status ───────────────────────────────────────────────────────────────

    /**
     * GET /api/bkash/agreement/status
     */
    public function status(Request $request): JsonResponse
    {
        $agreement = $request->user()->activeAgreement;

        return response()->json([
            'has_agreement' => (bool) $agreement,
            'created_at'    => $agreement?->created_at,
        ]);
    }
}
