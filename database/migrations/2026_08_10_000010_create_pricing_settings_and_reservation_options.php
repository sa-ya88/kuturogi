<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_weekend_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('friday_percent')->default(0);
            $table->unsignedInteger('saturday_percent')->default(0);
            $table->unsignedInteger('sunday_percent')->default(0);
            $table->unsignedInteger('holiday_percent')->default(0);
            $table->unsignedInteger('day_before_holiday_percent')->default(0);
            $table->timestamps();
        });

        Schema::create('pricing_season_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable()->unique();
            $table->string('name');
            $table->string('kind')->default('custom');
            $table->unsignedInteger('priority')->default(0);
            $table->string('adjustment_type')->default('surcharge');
            $table->date('date_from');
            $table->date('date_to');
            $table->unsignedInteger('percent')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pricing_child_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('子供');
            $table->unsignedInteger('percent_of_adult')->default(70);
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pricing_option_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable()->unique();
            $table->string('name');
            $table->unsignedInteger('price')->default(0);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pricing_cancel_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable()->unique();
            $table->string('label');
            $table->unsignedInteger('days_before_from')->default(0);
            $table->unsignedInteger('days_before_to')->default(0);
            $table->boolean('is_no_show')->default(false);
            $table->unsignedInteger('charge_percent')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->json('selected_option_fees')->nullable()->after('selected_choices');
            $table->unsignedInteger('room_count')->default(1)->after('guest_count');
            $table->unsignedInteger('adult_count')->nullable()->after('room_count');
            $table->unsignedInteger('child_count')->nullable()->after('adult_count');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['selected_option_fees', 'room_count', 'adult_count', 'child_count']);
        });

        Schema::dropIfExists('pricing_cancel_rules');
        Schema::dropIfExists('pricing_option_fees');
        Schema::dropIfExists('pricing_child_rates');
        Schema::dropIfExists('pricing_season_rates');
        Schema::dropIfExists('pricing_weekend_rules');
    }
};
