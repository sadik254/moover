<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('password_reset_code', 10)->nullable()->after('password');
            $table->timestamp('password_reset_code_sent_at')->nullable()->after('password_reset_code');
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['password_reset_code', 'password_reset_code_sent_at']);
        });
    }
};

