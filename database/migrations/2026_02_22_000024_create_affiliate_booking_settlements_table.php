<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_booking_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->decimal('gross_amount', 10, 2)->default(0);
            $table->decimal('affiliate_percent', 5, 2)->default(0);
            $table->decimal('platform_percent', 5, 2)->default(0);
            $table->decimal('affiliate_amount', 10, 2)->default(0);
            $table->decimal('platform_amount', 10, 2)->default(0);
            $table->string('currency', 10)->default('usd');
            $table->string('status', 20)->default('pending'); // pending, ready, on_hold, paid, failed
            $table->string('status_reason')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_booking_settlements');
    }
};

