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
        Schema::create('rezlive_logs', function (Blueprint $table) {
            $table->id();

            $table->string('type')->nullable();

            $table->longText('request_xml')->nullable();

            $table->longText('response_xml')->nullable();

            $table->json('request_payload')->nullable();

            $table->integer('status_code')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rezlive_logs');
    }
};
