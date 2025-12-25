<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('form_submissions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('company_id')->constrained()->cascadeOnDelete();
        $table->foreignId('form_id')->constrained('form_templates')->cascadeOnDelete();
        $table->longText('data_json');
        $table->timestamps();
    });

    }

    public function down(): void {
        Schema::dropIfExists('formsubmissions');
    }
};