<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicle_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
			$table->string('description')->nullable();
			$table->string('image')->nullable();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['company_id', 'name']);

        });
    }

    public function down(): void {
        Schema::dropIfExists('vehicle_classes');
    }
};