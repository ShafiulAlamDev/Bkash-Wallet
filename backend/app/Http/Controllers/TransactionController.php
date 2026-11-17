<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\PdfStatementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TransactionController extends Controller
{
    public function __construct(private PdfStatementService $pdf) {}

    /**
     * GET /api/transactions
     * Paginated transaction history with optional type filter.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type'     => 'nullable|in:credit,debit,refund',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);

        $query = $request->user()
            ->transactions()
            ->when($data['type'] ?? null, fn ($q, $type) => $q->where('type', $type));

        $transactions = $query->paginate($data['per_page'] ?? 15);

        return response()->json($transactions);
    }

    /**
     * GET /api/transactions/statement/download
     * Generates and streams a PDF statement via Gotenberg.
     */
    public function downloadStatement(Request $request): Response
    {
        $data = $request->validate([
            'from'   => 'nullable|date',
            'to'     => 'nullable|date|after_or_equal:from',
            'locale' => 'nullable|in:en,bn',
        ]);

        $user   = $request->user();
        $from   = $data['from'] ?? now()->startOfMonth()->toDateString();
        $to     = $data['to']   ?? now()->toDateString();
        $locale = $data['locale'] ?? $user->preferred_locale ?? 'en';

        $transactions = $user->transactions()
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->get();

        $pdfBytes = $this->pdf->generateStatement($user, $transactions, $from, $to, $locale);

        return response($pdfBytes, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"statement_{$from}_{$to}.pdf\"",
        ]);
    }
}
