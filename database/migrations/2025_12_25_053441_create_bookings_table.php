<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bookings', function (Blueprint $table) {
		$table->id();
		$table->foreignId('company_id')->constrained()->cascadeOnDelete();
		$table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
		$table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
		$table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();

		$table->enum('service_type', ['point_to_point','hourly','airport','custom']);
		$table->text('pickup_address');
		$table->text('dropoff_address')->nullable();
		$table->dateTime('pickup_time');
		$table->integer('passengers');

		$table->decimal('distance_km', 10, 2)->nullable();
		$table->decimal('base_price', 10, 2)->nullable();
		$table->decimal('extras_price', 10, 2)->nullable();
		$table->decimal('total_price', 10, 2)->nullable();

		$table->enum('status', ['pending','confirmed','assigned','on_route','completed','cancelled'])
			->default('pending');

		$table->text('notes')->nullable();

		$table->timestamps();
	});

    }

    public function down(): void {
        Schema::dropIfExists('bookings');
    }
};