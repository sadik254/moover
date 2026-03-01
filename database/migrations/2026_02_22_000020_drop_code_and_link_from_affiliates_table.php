<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            if (Schema::hasColumn('affiliates', 'code')) {
                $table->dropUnique(['code']);
                $table->dropColumn('code');
            }

            if (Schema::hasColumn('affiliates', 'link')) {
                $table->dropColumn('link');
            }
        });
    }

    public function down(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('address');
            $table->string('link')->nullable()->after('code');
        });
    }
};

