<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('customer_email', 255)->nullable();
            $table->string('voucher_code', 40)->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);

            // A pending request holds the vehicle only until this moment
            $table->timestamp('expires_at')->nullable();

            // customer | business | system  (reject / cancel / expire are all "cancelled")
            $table->string('cancelled_by', 20)->nullable();

            $table->timestamp('terms_accepted_at')->nullable();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->decimal('deposit_amount', 10, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'customer_email', 'voucher_code', 'discount_amount',
                'expires_at', 'cancelled_by', 'terms_accepted_at',
            ]);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('deposit_amount');
        });
    }
};