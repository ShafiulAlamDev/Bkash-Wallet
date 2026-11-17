<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Generates PDF statements via the Gotenberg microservice.
 * dompdf is explicitly banned per project requirements.
 *
 * Gotenberg docs: https://gotenberg.dev/docs/routes#html-file-into-pdf-route
 */
class PdfStatementService
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => config('bkash.gotenberg_url'),
            'timeout'  => 60,
        ]);
    }

    /**
     * Convert an HTML string to PDF bytes via Gotenberg.
     *
     * @param  string $html  Fully-qualified HTML document (including <html>, <head>, etc.)
     * @return string        Raw PDF bytes
     */
    public function htmlToPdf(string $html): string
    {
        try {
            $response = $this->http->post('/forms/chromium/convert/html', [
                'multipart' => [
                    [
                        'name'     => 'files',
                        'contents' => $html,
                        'filename' => 'index.html',
                    ],
                    [
                        'name'     => 'marginTop',
                        'contents' => '0.5',
                    ],
                    [
                        'name'     => 'marginBottom',
                        'contents' => '0.5',
                    ],
                    [
                        'name'     => 'marginLeft',
                        'contents' => '0.5',
                    ],
                    [
                        'name'     => 'marginRight',
                        'contents' => '0.5',
                    ],
                    [
                        'name'     => 'paperWidth',
                        'contents' => '8.27', // A4
                    ],
                    [
                        'name'     => 'paperHeight',
                        'contents' => '11.69',
                    ],
                ],
            ]);

            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            Log::error('Gotenberg PDF conversion failed', ['error' => $e->getMessage()]);
            throw new RuntimeException('PDF generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Build an HTML statement and return PDF bytes.
     */
    public function generateStatement(
        \App\Models\User $user,
        \Illuminate\Support\Collection $transactions,
        string $from,
        string $to,
        string $locale = 'en'
    ): string {
        $html = view('pdf.statement', compact('user', 'transactions', 'from', 'to', 'locale'))
            ->render();

        return $this->htmlToPdf($html);
    }
}
