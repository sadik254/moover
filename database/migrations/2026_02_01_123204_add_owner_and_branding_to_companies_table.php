<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Company owner (1 user → 1 company)
                $table->foreignId('user_id')
                    ->after('id')
                    ->constrained()
                    ->cascadeOnDelete()
                    ->unique();

            // Uploadcare file UUID or CDN URL
            $table->string('logo')->nullable()->after('timezone');

            // Public website or booking URL
            $table->string('url')->nullable()->after('logo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn(['user_id', 'logo', 'url']);
        });
    }
};
