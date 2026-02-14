<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->default('stripe');
            $table->string('currency', 10)->default('usd');
            $table->string('payment_intent_id')->unique();
            $table->string('payment_method_id')->nullable();
            $table->decimal('estimated_amount', 10, 2);
            $table->decimal('authorized_amount', 10, 2);
            $table->decimal('captured_amount', 10, 2)->nullable();
            $table->decimal('amount_to_capture', 10, 2)->nullable();
            $table->string('status', 50)->default('created');
            $table->string('failure_code')->nullable();
            $table->text('failure_message')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('booking_payments');
    }
};
