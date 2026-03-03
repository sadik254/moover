<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('affiliate_id')->nullable()->after('driver_id')->constrained()->nullOnDelete();
            $table->string('affiliate_status')->nullable()->after('status');
            $table->string('affiliate_reference')->nullable()->after('affiliate_status');
            $table->text('affiliate_notes')->nullable()->after('affiliate_reference');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['affiliate_id']);
            $table->dropColumn(['affiliate_id', 'affiliate_status', 'affiliate_reference', 'affiliate_notes']);
        });
    }
};

