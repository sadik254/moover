<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('company_id')->constrained('users')->nullOnDelete();
            $table->string('name')->nullable()->after('user_id');
            $table->string('email')->nullable()->unique()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->string('status')->nullable()->after('phone');
            $table->text('address')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['email']);
            $table->dropColumn(['user_id', 'name', 'email', 'phone', 'status', 'address']);
        });
    }
};

