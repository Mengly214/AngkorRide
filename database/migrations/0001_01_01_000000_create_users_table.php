<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('full_name', 150);

            $table->string('email', 255)
                ->nullable()
                ->unique();

            $table->string('phone', 20)
                ->unique();

            $table->text('password_hash');

            $table->enum('role', [
                'customer',
                'business_admin',
                'admin'
            ])->default('customer');

            $table->text('profile_photo_url')->nullable();

            $table->boolean('is_verified')->default(false);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('role');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};