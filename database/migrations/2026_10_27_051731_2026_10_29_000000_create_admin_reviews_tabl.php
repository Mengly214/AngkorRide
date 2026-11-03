<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // pending -> approved | rejected ; approved <-> suspended
            $table->string('admin_status', 20)->default('pending')->after('is_active');
            $table->text('admin_note')->nullable()->after('admin_status');
            $table->timestamp('reviewed_at')->nullable()->after('admin_note');
            $table->uuid('reviewed_by')->nullable()->after('reviewed_at');
            $table->index('admin_status');
        });

        // Shops that were already verified keep working as approved.
        DB::table('businesses')->where('is_verified', true)->update(['admin_status' => 'approved']);

        Schema::create('admin_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('admin_id')->nullable();
            $table->string('action', 40);
            $table->string('subject_type', 30)->nullable();
            $table->string('subject_id', 40)->nullable();
            $table->string('summary', 255);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_logs');

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex(['admin_status']);
            $table->dropColumn(['admin_status', 'admin_note', 'reviewed_at', 'reviewed_by']);
        });
    }
};