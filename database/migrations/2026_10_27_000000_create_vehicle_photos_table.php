<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            // "/storage/vehicles/abc.jpg" for uploads, a full URL for pasted links
            $table->text('path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['vehicle_id', 'sort_order']);
        });

        // Existing single photos become each vehicle's first (cover) photo.
        $now = now();
        DB::table('vehicles')->whereNotNull('photo_url')->where('photo_url', '!=', '')
            ->select('id', 'photo_url')->orderBy('id')
            ->each(function ($v) use ($now) {
                DB::table('vehicle_photos')->insert([
                    'vehicle_id' => $v->id,
                    'path' => $v->photo_url,
                    'sort_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_photos');
    }
};