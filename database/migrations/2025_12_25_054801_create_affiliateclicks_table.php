<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('affiliate_clicks', function (Blueprint $table) {
        $table->id();
        $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
        $table->string('ip_address');
        $table->text('user_agent')->nullable();
        $table->timestamps();
    });

    }

    public function down(): void {
        Schema::dropIfExists('affiliateclicks');
    }
};