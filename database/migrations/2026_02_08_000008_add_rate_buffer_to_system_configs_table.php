<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('system_configs', function (Blueprint $table) {
            $table->decimal('rate_buffer', 5, 2)->nullable()->after('wait_time_rate');
        });
    }

    public function down(): void {
        Schema::table('system_configs', function (Blueprint $table) {
            $table->dropColumn('rate_buffer');
        });
    }
};
