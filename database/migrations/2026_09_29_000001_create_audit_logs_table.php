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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->string('module')->index();
            $table->string('event')->index();
            $table->string('action')->nullable()->index();

            $table->text('description')->nullable();

            $table->string('usercode')->nullable()->index();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('access_level')->nullable();

            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();

            $table->string('booking_code')->nullable()->index();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();

            $table->string('status')->nullable()->index();

            $table->timestamps();

            $table->index(['module', 'event']);
            $table->index(['usercode', 'created_at']);
            $table->index(['booking_code', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
