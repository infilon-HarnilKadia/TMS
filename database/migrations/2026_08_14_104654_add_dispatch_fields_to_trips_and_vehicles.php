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
        Schema::table('trips', function (Blueprint $table) {
            $table->string('commodity_group')->nullable()->after('destination');
            $table->dateTime('estimated_departure')->nullable()->after('commodity_group');
            $table->dateTime('estimated_arrival')->nullable()->after('estimated_departure');
            $table->string('invoice_reference')->nullable()->after('estimated_arrival');
            $table->string('waybill_number')->nullable()->after('invoice_reference');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->unsignedInteger('tank_capacity_litres')->nullable()->after('fuel_type');
            $table->unsignedInteger('current_odometer_km')->nullable()->after('tank_capacity_litres');
            $table->unsignedInteger('starting_fuel_litres')->nullable()->after('current_odometer_km');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn([
                'commodity_group',
                'estimated_departure',
                'estimated_arrival',
                'invoice_reference',
                'waybill_number',
            ]);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'tank_capacity_litres',
                'current_odometer_km',
                'starting_fuel_litres',
            ]);
        });
    }
};
