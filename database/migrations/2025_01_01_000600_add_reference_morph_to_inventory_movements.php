<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The movement log records which document caused a stock change. A polymorphic
 * pair (rather than a hard FK) keeps the log intact if the referenced document
 * type is ever removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->nullableMorphs('reference');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropMorphs('reference');
        });
    }
};
