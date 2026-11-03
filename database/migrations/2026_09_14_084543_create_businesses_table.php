<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('owner_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('name', 150);

            $table->text('description')->nullable();

            $table->string('registration_no', 100)->nullable();

            $table->text('address')->nullable();

            $table->string('city', 100)
                ->default('Phnom Penh');

            $table->decimal('latitude', 9, 6)->nullable();

            $table->decimal('longitude', 9, 6)->nullable();

            $table->string('phone', 20)->nullable();

            $table->string('email', 255)->nullable();

            $table->text('logo_url')->nullable();

            $table->time('opening_time')->nullable();

            $table->time('closing_time')->nullable();

            $table->boolean('is_verified')->default(false);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('owner_user_id');

            $table->index('city');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};