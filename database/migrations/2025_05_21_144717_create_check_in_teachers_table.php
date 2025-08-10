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
        Schema::create('check_in_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignid('teacher_id')->constrained()->onDelete('cascade');
            $table->foreignid('student_id')->constrained()->onDelete('cascade');
            $table->foreignid('class_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->boolean('checked');
            $table->enum('sessions',['1','2','3','4','5','6','7']);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_in_teachers');
    }
};
