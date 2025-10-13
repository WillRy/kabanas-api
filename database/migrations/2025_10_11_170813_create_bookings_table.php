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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->dateTime('startDate');
            $table->dateTime('endDate');
            $table->integer('numNights');
            $table->integer('numGuests');
            $table->decimal('propertyPrice', 10, 2);
            $table->decimal('extrasPrice', 10, 2);
            $table->decimal('totalPrice', 10, 2);
            $table->string('status')->default('unconfirmed');
            $table->boolean('hasBreakfast')->default(false);
            $table->boolean('isPaid')->default(false);
            $table->text('observations')->nullable();

            $table->foreignId('guest_id')->constrained()->onDelete('cascade');
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
