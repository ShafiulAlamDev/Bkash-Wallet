<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'trx_id'                  => $this->trx_id,
            'payment_id'              => $this->payment_id,
            'type'                    => $this->type,
            'amount'                  => (float) $this->amount,
            'charge'                  => (float) $this->charge,
            'balance_after'           => (float) $this->balance_after,
            'currency'                => $this->currency,
            'status'                  => $this->status,
            'description'             => $this->description,
            'refunded_amount'         => (float) $this->refunded_amount,
            'refundable_amount'       => (float) $this->refundable_amount,
            'is_refundable'           => $this->isRefundable(),
            'original_transaction_id' => $this->original_transaction_id,
            'created_at'              => $this->created_at->toISOString(),
            'formatted_date'          => $this->created_at->format('d M Y, h:i A'),
        ];
    }
}
