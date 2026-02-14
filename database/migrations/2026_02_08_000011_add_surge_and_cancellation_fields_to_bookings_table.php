<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('cancellation_fee', 10, 2)->nullable()->after('rate_buffer_amount');
            $table->decimal('surge_rate', 5, 2)->nullable()->after('cancellation_fee');
            $table->decimal('surge_rate_amount', 10, 2)->nullable()->after('surge_rate');
        });
    }

    public function down(): void {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'cancellation_fee',
                'surge_rate',
                'surge_rate_amount',
            ]);
        });
    }
};
