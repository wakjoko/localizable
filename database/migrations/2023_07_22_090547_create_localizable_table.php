<?php

use Wakjoko\Localizable\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(config('localizable.table'), function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->string('attribute');
            $table->string('locale');
            $table->string('value');
            $table->nullableTimestamps();
            $table->unique(Model::uniqueKey);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('localizable.table'));
    }
};
