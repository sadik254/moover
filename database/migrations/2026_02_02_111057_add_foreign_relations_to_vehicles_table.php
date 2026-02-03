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
        Schema::table('vehicles', function (Blueprint $table) {
            //
            $table->foreignId('vehicle_class_id')
            ->constrained()
            ->cascadeOnDelete();
            $table->string('status')->nullable();
            $table->string('category')->nullable()->change();
            $table->string('plate_number')->nullable();
            $table->unique('plate_number');
            $table->string('color')->nullable();
            $table->string('model')->nullable();
            $table->string('image')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Drop the foreign key and the column
            $table->dropForeign(['vehicle_class_id']);
            $table->dropColumn('vehicle_class_id');
            
            // Remove the new column
            $table->dropColumn('status');
            $table->dropColumn('plate_number');
            $table->dropColumn('color');
            $table->dropColumn('model');
            $table->dropColumn('image');
            
            // Revert category to NOT NULL (remove nullable)
            $table->string('category')->nullable(false)->change();
        });
    }
};
