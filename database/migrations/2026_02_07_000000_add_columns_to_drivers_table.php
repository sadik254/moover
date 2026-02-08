<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('drivers', function (Blueprint $table) {
            $table->date('license_expiry')->nullable()->after('license_number');
            $table->string('status')->nullable()->after('license_expiry');
            $table->string('employment_type')->nullable()->after('status');
            $table->string('commission')->nullable()->after('employment_type');
            $table->string('license_front')->nullable()->after('commission');
            $table->string('license_back')->nullable()->after('license_front');
            $table->text('address')->nullable()->after('license_back');
        });
    }

    public function down(): void {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn([
                'license_expiry',
                'status',
                'employment_type',
                'commission',
                'license_front',
                'license_back',
                'address',
            ]);
        });
    }
};
