<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Days a shop takes a vehicle off the calendar (workshop, private use...).
        // Stored as Asia/Phnom_Penh wall-clock times, like bookings:
        // start = first day 00:00, end = the morning AFTER the last blocked day.
        Schema::create('vehicle_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->string('reason', 20)->default('maintenance'); // maintenance | private | other
            $table->string('note', 120)->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'start_datetime', 'end_datetime']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_blocks');
    }
};