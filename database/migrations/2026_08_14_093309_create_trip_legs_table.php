<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_legs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->integer('leg_number')->default(1);
            $table->enum('leg_type', ['primary', 'return', 'backhaul'])->default('primary');
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('origin');
            $table->string('destination');
            $table->decimal('payload_weight_tons', 8, 2)->nullable();
            $table->string('commodity_category')->nullable();
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->enum('status', ['pending', 'in_transit', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_legs');
    }
};
