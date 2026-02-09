<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('system_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->decimal('tax_rate', 10, 2)->nullable();
            $table->decimal('base_price_flat', 10, 2)->nullable();
            $table->decimal('cancellation_fee', 10, 2)->nullable();
            $table->decimal('surge_rate', 10, 2)->nullable();
            $table->decimal('wait_time_rate', 10, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->json('service_zones')->nullable();
            $table->string('platform_name')->nullable();
            $table->string('primary_brand_color', 20)->nullable();
            $table->string('secondary_brand_color', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('system_configs');
    }
};
