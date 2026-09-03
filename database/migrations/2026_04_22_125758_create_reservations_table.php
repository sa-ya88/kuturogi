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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('checkin_date');
            $table->date('checkout_date');
            $table->integer('guest_count');
            $table->integer('total_price');
            $table->string('status')->default('pending');
            $table->string('guest_name')->nullable();
            $table->string('guest_name_kana')->nullable();
            $table->string('guest_tel')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_zip_code')->nullable();
            $table->string('guest_address')->nullable();
            $table->string('guest_building')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
