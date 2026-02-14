<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('airport_fees', 10, 2)->nullable()->after('others');
            $table->decimal('congestion_charge', 10, 2)->nullable()->after('airport_fees');
            $table->decimal('taxes_amount', 10, 2)->nullable()->after('congestion_charge');
            $table->decimal('gratuity_amount', 10, 2)->nullable()->after('taxes_amount');
            $table->decimal('rate_buffer', 5, 2)->nullable()->after('gratuity_amount');
            $table->decimal('rate_buffer_amount', 10, 2)->nullable()->after('rate_buffer');
        });
    }

    public function down(): void {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'airport_fees',
                'congestion_charge',
                'taxes_amount',
                'gratuity_amount',
                'rate_buffer',
                'rate_buffer_amount',
            ]);
        });
    }
};
