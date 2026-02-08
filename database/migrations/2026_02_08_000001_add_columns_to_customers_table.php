<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_company')->nullable()->after('company_id');
            $table->string('customer_type')->nullable()->after('customer_company');
            $table->string('password')->nullable()->after('customer_type');
            $table->text('dispatch_note')->nullable()->after('password');
            $table->string('preferred_service_level')->nullable()->after('dispatch_note');
        });
    }

    public function down(): void {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'customer_company',
                'customer_type',
                'password',
                'dispatch_note',
                'preferred_service_level',
            ]);
        });
    }
};
