<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Customer who makes the booking
            $table->foreignUuid('customer_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Vehicle being rented
            $table->foreignUuid('vehicle_id')
                ->constrained('vehicles')
                ->restrictOnDelete();

            // Business that owns the vehicle
            $table->foreignUuid('business_id')
                ->constrained('businesses')
                ->restrictOnDelete();

            // Rental period
            $table->dateTime('start_datetime');

            $table->dateTime('end_datetime');

            // Pickup and return
            $table->string('pickup_location', 255);

            $table->string('return_location', 255)
                ->nullable();

            // Booking status
            $table->enum('status', [
                'pending',
                'confirmed',
                'ongoing',
                'completed',
                'cancelled'
            ])->default('pending');

            // Pricing
            $table->enum('rate_type', [
                'hourly',
                'daily'
            ])->default('daily');

            // Rate at the time the booking was created
            $table->decimal('rate_amount', 10, 2);

            // Final rental price
            $table->decimal('total_price', 10, 2);

            // Deposit
            $table->decimal('deposit_amount', 10, 2)
                ->default(0);

            $table->boolean('deposit_refunded')
                ->default(false);

            // Payment
            $table->enum('payment_status', [
                'unpaid',
                'paid',
                'partially_refunded',
                'refunded',
                'failed'
            ])->default('unpaid');

            $table->string('payment_method', 30)
                ->nullable();

            // Customer notes
            $table->text('customer_notes')
                ->nullable();

            // Booking timestamps
            $table->timestamp('confirmed_at')
                ->nullable();

            $table->timestamp('picked_up_at')
                ->nullable();

            $table->timestamp('returned_at')
                ->nullable();

            $table->timestamp('cancelled_at')
                ->nullable();

            $table->text('cancellation_reason')
                ->nullable();

            $table->timestamps();

            // Indexes
            $table->index('customer_id');

            $table->index('vehicle_id');

            $table->index('business_id');

            $table->index('status');

            // Useful for checking vehicle availability
            $table->index([
                'vehicle_id',
                'start_datetime',
                'end_datetime'
            ]);

           
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};