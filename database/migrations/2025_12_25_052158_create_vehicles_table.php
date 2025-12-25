<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('company_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->string('category');
        $table->integer('capacity');
        $table->integer('luggage')->nullable();
        $table->decimal('hourly_rate', 10, 2)->nullable();
        $table->decimal('per_km_rate', 10, 2)->nullable();
        $table->decimal('airport_rate', 10, 2)->nullable();
        $table->timestamps();
    });

    }

    public function down(): void {
        Schema::dropIfExists('vehicles');
    }
};