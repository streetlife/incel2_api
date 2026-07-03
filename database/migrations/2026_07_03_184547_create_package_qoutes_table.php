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
        Schema::create('package_qoutes', function (Blueprint $table) {
            $table->id();
            $table->string('package_name');
            $table->decimal('price', 10, 2)->default(0.00);
            $table->boolean('flight_booking')->default(false);
            $table->date('departure_date');
            $table->unsignedTinyInteger('number_of_travelers')->default(1);
            $table->string('full_name');
            $table->string('phone_number');
            $table->string('email_address');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_qoutes');
    }
};
