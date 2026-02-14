<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('system_configs', function (Blueprint $table) {
            $table->decimal('gratuity_percentage', 5, 2)->nullable()->after('rate_buffer');
        });
    }

    public function down(): void {
        Schema::table('system_configs', function (Blueprint $table) {
            $table->dropColumn('gratuity_percentage');
        });
    }
};
