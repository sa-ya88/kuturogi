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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // 誰が（ログインユーザー）
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete(); // どのプランで
            $table->date('checkin_date');   // チェックイン日
            $table->date('checkout_date');  // チェックアウト日
            $table->integer('guest_count'); // 宿泊人数
            $table->integer('total_price'); // 合計金額
            $table->string('status')->default('pending'); // 予約状態（確定・キャンセル待ち等）
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
