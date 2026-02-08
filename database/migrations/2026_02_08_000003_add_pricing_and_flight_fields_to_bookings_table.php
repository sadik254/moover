<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('bookings', function (Blueprint $table) {
            $table->integer('child_seats')->nullable()->after('passengers');
            $table->integer('bags')->nullable()->after('child_seats');
            $table->string('flight_number')->nullable()->after('bags');
            $table->string('airlines')->nullable()->after('flight_number');

            $table->decimal('taxes', 10, 2)->nullable()->after('extras_price');
            $table->decimal('gratuity', 10, 2)->nullable()->after('taxes');
            $table->decimal('parking', 10, 2)->nullable()->after('gratuity');
            $table->decimal('others', 10, 2)->nullable()->after('parking');
            $table->decimal('final_price', 10, 2)->nullable()->after('total_price');

            $table->string('payment_method')->nullable()->after('final_price');
            $table->string('payment_status')->nullable()->after('payment_method');
        });
    }

    public function down(): void {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'child_seats',
                'bags',
                'flight_number',
                'airlines',
                'taxes',
                'gratuity',
                'parking',
                'others',
                'payment_method',
                'payment_status',
            ]);
        });
    }
};
