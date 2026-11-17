<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('trx_id')->unique()->nullable();    // bKash transaction ID
            $table->string('payment_id')->unique()->nullable(); // bKash payment ID
            $table->enum('type', ['credit', 'debit', 'refund']);
            $table->decimal('amount', 12, 2);
            $table->decimal('charge', 12, 2)->default(0.00);
            $table->decimal('balance_after', 12, 2);
            $table->string('currency', 5)->default('BDT');
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->string('description')->nullable();
            // For refunds: references to original transaction
            $table->foreignId('original_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->decimal('refunded_amount', 12, 2)->default(0.00); // cumulative refund amount
            $table->json('bkash_response')->nullable(); // raw bKash response for audit
            $table->timestamps();

            $table->index(['user_id', 'type', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
