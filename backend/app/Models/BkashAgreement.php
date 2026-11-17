<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class BkashAgreement extends Model
{
    protected $fillable = [
        'user_id',
        'agreement_id_encrypted',
        'payer_reference',
        'status',
    ];

    protected $hidden = ['agreement_id_encrypted'];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Encrypted agreementId accessor/mutator ───────────────────────────────

    /**
     * Store the plain agreementId encrypted at rest using Laravel's Crypt facade
     * (AES-256-CBC under the hood, key derived from APP_KEY or BKASH_ENCRYPT_KEY).
     */
    public function setAgreementIdAttribute(string $plainId): void
    {
        $this->attributes['agreement_id_encrypted'] = Crypt::encryptString($plainId);
    }

    public function getAgreementIdAttribute(): ?string
    {
        if (empty($this->attributes['agreement_id_encrypted'])) {
            return null;
        }

        return Crypt::decryptString($this->attributes['agreement_id_encrypted']);
    }
}
