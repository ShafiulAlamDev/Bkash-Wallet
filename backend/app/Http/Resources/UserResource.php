<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'email'            => $this->email,
            'phone'            => $this->phone,
            'wallet_balance'   => (float) $this->wallet_balance,
            'preferred_locale' => $this->preferred_locale,
            'active_agreement' => $this->whenLoaded('activeAgreement', function () {
                return $this->activeAgreement ? [
                    'id'         => $this->activeAgreement->id,
                    'status'     => $this->activeAgreement->status,
                    'created_at' => $this->activeAgreement->created_at->toISOString(),
                ] : null;
            }),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
