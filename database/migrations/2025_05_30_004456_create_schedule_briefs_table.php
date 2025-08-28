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
        Schema::create('schedule_briefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->enum('day',['sunday','monday','tuesday','wednesday','thursday','friday','saturday',]);
            $table->enum('semester',['first','second']);
            $table->boolean('current')->default(true);
            $table->string('year');//e.g. 2024/2025
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_brief');
    }
};
