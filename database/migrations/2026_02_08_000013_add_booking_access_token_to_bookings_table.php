<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('booking_access_token', 80)->nullable()->unique()->after('customer_id');
        });
    }

    public function down(): void {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['booking_access_token']);
            $table->dropColumn('booking_access_token');
        });
    }
};
