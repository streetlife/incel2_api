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
        Schema::create('airport_protocols', function (Blueprint $table) {
            $table->id();
            $table->string('service_type')->nullable();
            $table->string('airport_name')->nullable();
            $table->string('flight_number')->nullable();
            $table->json('service_required')->nullable();
            $table->text('additional_info')->nullable();
            $table->string('booking_code')->nullable();
            $table->string('airline')->nullable();
            $table->string('number_of_passengers')->nullable();
           
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airport_protocols');
    }
};
