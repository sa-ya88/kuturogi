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
        Schema::table('users', function (Blueprint $table) {
            $table->string('name_kana')->nullable()->after('name');
            $table->date('birthday')->nullable()->after('name_kana');
            $table->string('gender')->nullable()->after('birthday');
            $table->string('zip_code')->nullable()->after('gender');
            $table->string('address')->nullable()->after('zip_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['name_kana', 'birthday', 'gender', 'zip_code', 'address']);
        });
    }
};
