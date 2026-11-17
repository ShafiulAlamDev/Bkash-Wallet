<?php

namespace Database\Factories;

use App\Models\BkashAgreement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class BkashAgreementFactory extends Factory
{
    protected $model = BkashAgreement::class;

    public function definition(): array
    {
        return [
            'user_id'                => User::factory(),
            'agreement_id_encrypted' => Crypt::encryptString('AGR' . strtoupper(Str::random(12))),
            'payer_reference'        => '017' . fake()->numerify('########'),
            'status'                 => 'active',
        ];
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }
}
