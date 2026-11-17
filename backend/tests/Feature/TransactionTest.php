<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_transactions(): void
    {
        $user = User::factory()->create();

        Transaction::factory()->count(3)->create(['user_id' => $user->id, 'type' => 'credit', 'status' => 'completed']);

        $this->actingAs($user, 'sanctum')
             ->getJson('/api/transactions')
             ->assertOk()
             ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    public function test_user_can_filter_transactions_by_type(): void
    {
        $user = User::factory()->create();

        Transaction::factory()->create(['user_id' => $user->id, 'type' => 'credit', 'status' => 'completed']);
        Transaction::factory()->create(['user_id' => $user->id, 'type' => 'debit',  'status' => 'completed']);

        $response = $this->actingAs($user, 'sanctum')
                         ->getJson('/api/transactions?type=credit');

        $response->assertOk();
        $this->assertEquals(1, $response->json('total'));
    }

    public function test_cannot_refund_non_own_transaction(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $txn = Transaction::factory()->create([
            'user_id' => $owner->id,
            'type'    => 'credit',
            'status'  => 'completed',
            'amount'  => 100,
        ]);

        $this->actingAs($other, 'sanctum')
             ->postJson("/api/transactions/{$txn->id}/refund", ['amount' => 50])
             ->assertForbidden();
    }
}
