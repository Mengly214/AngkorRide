<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name', 50)->unique();

            $table->text('description')->nullable();

            $table->smallInteger('passenger_capacity')
                ->default(1);

            $table->boolean('requires_license')
                ->default(false);

            $table->text('icon_url')->nullable();

            $table->boolean('is_active')
                ->default(true); 

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');
    }
};