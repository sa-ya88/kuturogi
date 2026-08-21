<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->json('details')->nullable();
        });

        $default = json_encode([
            'facilities' => ['バス', 'シャワー', 'トイレ', '冷暖房', 'テレビ', '冷蔵庫'],
            'internet' => '全室Wi-Fi無料',
            'smoking' => '全室禁煙（喫煙スペースあり）',
            'amenities' => ['タオル', '歯ブラシ', '浴衣', 'ドライヤー', '石鹸類'],
        ], JSON_UNESCAPED_UNICODE);

        DB::table('rooms')->whereNull('details')->update(['details' => $default]);
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('details');
        });
    }
};
