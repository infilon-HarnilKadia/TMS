<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table): void {
            $table->id();
            $table->string('trip_number')->unique();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->string('origin');
            $table->string('destination');
            $table->decimal('total_distance_km', 10, 2)->nullable();
            $table->decimal('allocated_payload_tons', 8, 2)->nullable();
            $table->enum('status', ['draft', 'planned', 'in_transit', 'completed', 'cancelled'])->default('draft');
            $table->decimal('estimated_fuel_cost', 12, 2)->nullable();
            $table->decimal('actual_fuel_cost', 12, 2)->nullable();
            $table->decimal('estimated_expenses', 12, 2)->nullable();
            $table->decimal('actual_expenses', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
