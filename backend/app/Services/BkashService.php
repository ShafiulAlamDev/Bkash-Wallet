<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BkashService
{
    private Client $http;
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('bkash.base_url');
        $this->http    = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30,
        ]);
    }

    // ─── Token Management ─────────────────────────────────────────────────────

    /**
     * Returns a cached grant token, refreshing when needed.
     * Token is cached in Redis for slightly less than bKash's 1-hour TTL.
     */
    public function getToken(): string
    {
        return Cache::remember('bkash:token', config('bkash.token_ttl'), function () {
            return $this->grantToken();
        });
    }

    private function grantToken(): string
    {
        $response = $this->http->post('/tokenized/checkout/token/grant', [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'username'      => config('bkash.username'),
                'password'      => config('bkash.password'),
            ],
            'json' => [
                'app_key'    => config('bkash.app_key'),
                'app_secret' => config('bkash.app_secret'),
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if (empty($data['id_token'])) {
            Log::error('bKash token grant failed', $data);
            throw new RuntimeException('bKash token grant failed: ' . ($data['statusMessage'] ?? 'Unknown error'));
        }

        return $data['id_token'];
    }

    // ─── Agreement (Module 01) ────────────────────────────────────────────────

    /**
     * Initiate agreement creation — returns the bKash redirect URL.
     */
    public function createAgreement(string $payerReference): array
    {
        return $this->post('/tokenized/checkout/agreement/create', [
            'mode'          => '0000',
            'payerReference' => $payerReference,
            'callbackURL'   => config('bkash.agreement_callback_url'),
        ]);
    }

    /**
     * Execute agreement after user approval on bKash page.
     */
    public function executeAgreement(string $paymentID): array
    {
        return $this->post('/tokenized/checkout/agreement/execute', [
            'paymentID' => $paymentID,
        ]);
    }

    // ─── Payment with Agreement (Module 02) ──────────────────────────────────

    /**
     * Create a payment using a pre-established agreement (no OTP required).
     */
    public function createPaymentWithAgreement(
        string $agreementId,
        float  $amount,
        string $merchantInvoiceNumber,
        string $payerReference
    ): array {
        return $this->post('/tokenized/checkout/payment-with-agreement/create', [
            'mode'                  => '0001',
            'payerReference'        => $payerReference,
            'callbackURL'           => config('bkash.callback_url'),
            'agreementID'           => $agreementId,
            'amount'                => number_format($amount, 2, '.', ''),
            'currency'              => 'BDT',
            'intent'                => 'sale',
            'merchantInvoiceNumber' => $merchantInvoiceNumber,
        ]);
    }

    /**
     * Execute a payment after creation.
     */
    public function executePayment(string $paymentID): array
    {
        return $this->post('/tokenized/checkout/payment-with-agreement/execute', [
            'paymentID' => $paymentID,
        ]);
    }

    // ─── Refund (Module 03) ───────────────────────────────────────────────────

    /**
     * Initiate a (partial) refund on a completed transaction.
     */
    public function refund(
        string $trxId,
        string $paymentId,
        float  $amount,
        string $reason = ''
    ): array {
        return $this->post('/tokenized/checkout/payment/refund', [
            'paymentID' => $paymentId,
            'trxID'     => $trxId,
            'amount'    => number_format($amount, 2, '.', ''),
            'currency'  => 'BDT',
            'reason'    => $reason,
        ]);
    }

    // ─── Internal HTTP helper ─────────────────────────────────────────────────

    private function post(string $path, array $payload): array
    {
        try {
            $response = $this->http->post($path, [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    'Authorization' => $this->getToken(),
                    'X-APP-Key'     => config('bkash.app_key'),
                ],
                'json' => $payload,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            Log::info("bKash API [{$path}]", ['response' => $data]);

            return $data;
        } catch (RequestException $e) {
            $body = $e->hasResponse()
                ? $e->getResponse()->getBody()->getContents()
                : $e->getMessage();

            Log::error("bKash API error [{$path}]", ['error' => $body]);
            throw new RuntimeException("bKash API error: {$body}");
        }
    }
}
