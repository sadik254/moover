<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->string('payout_mode', 20)->default('percentage')->after('address');
            $table->decimal('affiliate_payout_percent', 5, 2)->default(70)->after('payout_mode');
            $table->decimal('platform_commission_percent', 5, 2)->default(30)->after('affiliate_payout_percent');
            $table->string('stripe_connect_account_id')->nullable()->after('platform_commission_percent');
            $table->string('payout_currency', 10)->default('usd')->after('stripe_connect_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropColumn([
                'payout_mode',
                'affiliate_payout_percent',
                'platform_commission_percent',
                'stripe_connect_account_id',
                'payout_currency',
            ]);
        });
    }
};

