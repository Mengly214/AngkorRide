<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('business_id')
                ->constrained('businesses')
                ->restrictOnDelete();

            $table->foreignUuid('vehicle_type_id')
                ->constrained('vehicle_types')
                ->restrictOnDelete();
            
            $table->string('plate_number', 20)->unique();

            $table->string('brand', 100)->nullable();

            $table->string('model', 100)->nullable();

            $table->string('color', 30)->nullable();

            $table->smallInteger('year')->nullable();

            $table->text('condition_notes')->nullable();

            $table->integer('mileage')->nullable();

            $table->decimal('price_per_hour', 10, 2)->nullable();

            $table->decimal('price_per_day', 10, 2);

            $table->text('photo_url')->nullable();

            $table->string('pick_up_location', 255)->nullable();

            $table->enum('status', [
                'available',
                'rented',
                'maintenance',
                'retired'
            ])->default('available');

            $table->timestamps();

            $table->index('business_id');

            $table->index('vehicle_type_id');

            $table->index('status');
 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
