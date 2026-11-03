<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // NULL business_id = a platform-wide code; otherwise only for that shop
            $table->foreignUuid('business_id')->nullable()
                ->constrained('businesses')->cascadeOnDelete();

            $table->string('code', 40)->unique();
            $table->string('description', 255)->nullable();

            $table->string('type', 10)->default('percent');   // percent | fixed
            $table->decimal('value', 10, 2);                   // 10 = 10% or $10

            $table->decimal('min_subtotal', 10, 2)->default(0);
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('business_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};