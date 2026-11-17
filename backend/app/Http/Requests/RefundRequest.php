<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled in the controller (ownership check)
        return true;
    }

    public function rules(): array
    {
        $transaction = $this->route('transaction');
        $maxRefundable = $transaction
            ? (float) $transaction->amount - (float) $transaction->refunded_amount
            : 0;

        return [
            'amount' => ['required', 'numeric', 'min:1', "max:{$maxRefundable}"],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.max' => 'Refund amount exceeds the refundable balance for this transaction.',
        ];
    }
}
