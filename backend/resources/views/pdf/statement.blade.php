<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $locale === 'bn' ? 'লেনদেন বিবরণী' : 'Transaction Statement' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 13px; color: #1a1a2e; background: #fff; }

        .header { background: linear-gradient(135deg, #e2136e 0%, #a0094f 100%); color: white; padding: 28px 36px; display: flex; justify-content: space-between; align-items: center; }
        .header .brand { font-size: 26px; font-weight: 800; letter-spacing: 1px; }
        .header .brand span { font-size: 12px; font-weight: 400; display: block; opacity: 0.85; margin-top: 2px; }
        .header .report-title { text-align: right; font-size: 14px; opacity: 0.9; }
        .header .report-title strong { font-size: 18px; display: block; }

        .meta { padding: 20px 36px; background: #fafafa; border-bottom: 1px solid #eee; display: flex; gap: 40px; }
        .meta-item label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
        .meta-item p { font-size: 14px; font-weight: 600; margin-top: 2px; }

        .summary { display: flex; gap: 16px; padding: 20px 36px; background: #fff; border-bottom: 1px solid #eee; }
        .summary-card { flex: 1; padding: 14px 18px; border-radius: 8px; }
        .summary-card.credit  { background: #e8f5e9; border-left: 4px solid #43a047; }
        .summary-card.debit   { background: #fff3e0; border-left: 4px solid #fb8c00; }
        .summary-card.refund  { background: #e3f2fd; border-left: 4px solid #1e88e5; }
        .summary-card label   { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #666; }
        .summary-card p       { font-size: 18px; font-weight: 700; margin-top: 4px; }

        table { width: 100%; border-collapse: collapse; margin-top: 0; }
        thead th { background: #e2136e; color: white; padding: 10px 14px; font-size: 12px; text-align: left; font-weight: 600; }
        tbody tr:nth-child(even) { background: #fdf2f7; }
        tbody tr:hover { background: #fce4ec; }
        tbody td { padding: 10px 14px; border-bottom: 1px solid #f0f0f0; font-size: 12px; vertical-align: middle; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .badge-credit  { background: #e8f5e9; color: #2e7d32; }
        .badge-debit   { background: #fff3e0; color: #e65100; }
        .badge-refund  { background: #e3f2fd; color: #1565c0; }
        .badge-completed { background: #e8f5e9; color: #2e7d32; }
        .badge-failed  { background: #ffebee; color: #c62828; }
        .badge-pending { background: #fffde7; color: #f57f17; }

        .amount-credit { color: #2e7d32; font-weight: 700; }
        .amount-debit  { color: #c62828; font-weight: 700; }
        .amount-refund { color: #1565c0; font-weight: 700; }

        .footer { padding: 18px 36px; background: #fafafa; border-top: 2px solid #e2136e; margin-top: 10px; display: flex; justify-content: space-between; font-size: 11px; color: #888; }

        .no-data { text-align: center; padding: 48px; color: #aaa; font-size: 15px; }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="brand">
        bKash Wallet
        <span>{{ $locale === 'bn' ? 'ডিজিটাল ওয়ালেট সেবা' : 'Digital Wallet Service' }}</span>
    </div>
    <div class="report-title">
        <strong>{{ $locale === 'bn' ? 'লেনদেন বিবরণী' : 'Transaction Statement' }}</strong>
        {{ $locale === 'bn' ? 'তৈরির তারিখ:' : 'Generated:' }} {{ now()->format('d M Y, h:i A') }}
    </div>
</div>

<!-- Account Meta -->
<div class="meta">
    <div class="meta-item">
        <label>{{ $locale === 'bn' ? 'নাম' : 'Account Holder' }}</label>
        <p>{{ $user->name }}</p>
    </div>
    <div class="meta-item">
        <label>{{ $locale === 'bn' ? 'ফোন' : 'Phone' }}</label>
        <p>{{ $user->phone ?? '—' }}</p>
    </div>
    <div class="meta-item">
        <label>{{ $locale === 'bn' ? 'সময়কাল' : 'Period' }}</label>
        <p>{{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</p>
    </div>
    <div class="meta-item">
        <label>{{ $locale === 'bn' ? 'বর্তমান ব্যালেন্স' : 'Current Balance' }}</label>
        <p>৳ {{ number_format($user->wallet_balance, 2) }}</p>
    </div>
</div>

<!-- Summary Cards -->
@php
    $credits = $transactions->where('type','credit')->sum('amount');
    $debits  = $transactions->where('type','debit')->sum('amount');
    $refunds = $transactions->where('type','refund')->sum('amount');
@endphp
<div class="summary">
    <div class="summary-card credit">
        <label>{{ $locale === 'bn' ? 'মোট জমা' : 'Total Credits' }}</label>
        <p>৳ {{ number_format($credits, 2) }}</p>
    </div>
    <div class="summary-card debit">
        <label>{{ $locale === 'bn' ? 'মোট খরচ' : 'Total Debits' }}</label>
        <p>৳ {{ number_format($debits, 2) }}</p>
    </div>
    <div class="summary-card refund">
        <label>{{ $locale === 'bn' ? 'মোট রিফান্ড' : 'Total Refunds' }}</label>
        <p>৳ {{ number_format($refunds, 2) }}</p>
    </div>
</div>

<!-- Transactions Table -->
@if($transactions->isEmpty())
    <div class="no-data">
        {{ $locale === 'bn' ? 'এই সময়কালে কোনো লেনদেন নেই।' : 'No transactions found for this period.' }}
    </div>
@else
<table>
    <thead>
        <tr>
            <th>{{ $locale === 'bn' ? 'তারিখ' : 'Date' }}</th>
            <th>{{ $locale === 'bn' ? 'বিবরণ' : 'Description' }}</th>
            <th>{{ $locale === 'bn' ? 'ট্রানজেকশন আইডি' : 'Transaction ID' }}</th>
            <th>{{ $locale === 'bn' ? 'ধরন' : 'Type' }}</th>
            <th>{{ $locale === 'bn' ? 'অবস্থা' : 'Status' }}</th>
            <th style="text-align:right">{{ $locale === 'bn' ? 'পরিমাণ' : 'Amount' }}</th>
            <th style="text-align:right">{{ $locale === 'bn' ? 'পরবর্তী ব্যালেন্স' : 'Balance After' }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $txn)
        <tr>
            <td>{{ $txn->created_at->format('d M Y') }}<br><small style="color:#aaa">{{ $txn->created_at->format('h:i A') }}</small></td>
            <td>{{ $txn->description ?? '—' }}</td>
            <td style="font-family:monospace;font-size:11px">{{ $txn->trx_id ?? $txn->payment_id ?? '—' }}</td>
            <td><span class="badge badge-{{ $txn->type }}">{{ strtoupper($txn->type) }}</span></td>
            <td><span class="badge badge-{{ $txn->status }}">{{ strtoupper($txn->status) }}</span></td>
            <td style="text-align:right" class="amount-{{ $txn->type }}">
                {{ in_array($txn->type, ['debit','refund']) ? '−' : '+' }}৳ {{ number_format($txn->amount, 2) }}
            </td>
            <td style="text-align:right">৳ {{ number_format($txn->balance_after, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<!-- Footer -->
<div class="footer">
    <span>{{ $locale === 'bn' ? 'এটি একটি স্বয়ংক্রিয়ভাবে তৈরি বিবরণী।' : 'This is a system-generated statement.' }}</span>
    <span>bKash Wallet &copy; {{ date('Y') }}</span>
</div>

</body>
</html>
