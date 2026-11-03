<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            // The shop the money is for (so admin can see what each shop is owed).
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();

            $table->string('provider', 20);                 // test | payway
            $table->string('method', 20)->nullable();       // khqr, card, ... when the provider tells us
            $table->string('tran_id', 30)->unique();         // our reference, sent to the provider

            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');

            // pending | paid | failed | expired | refund_due | refunded | review
            $table->string('status', 20)->default('pending');
            $table->string('provider_ref', 60)->nullable(); // the bank's reference
            $table->text('note')->nullable();               // why it failed / needs review / was refunded
            $table->json('meta')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('business_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};