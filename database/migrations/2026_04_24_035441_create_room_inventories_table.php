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
        Schema::create('room_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade'); // 部屋
            $table->date('date'); // 日付
            $table->integer('remains')->default(0); // 残り室数
            $table->timestamps();
            $table->unique(['room_id', 'date']); //ユニーク制約
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_inventories');
    }
};
