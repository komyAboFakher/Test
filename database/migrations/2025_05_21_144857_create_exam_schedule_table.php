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
        Schema::create('exam_schedule', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->string('year');
            $table->string('schedule_pdf');
            $table->enum('type',['final','mid-term']);
            $table->enum('semester',['first','second']);
            $table->enum('grade',['1','2','3','4','5','6','7','8','9','10','11','12']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_schedule');
    }
};
