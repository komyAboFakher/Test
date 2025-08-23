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
        Schema::create('academics', function (Blueprint $table) {
            $table->id();
            $table->enum('academic_semester',['First','Second']);
            $table->string('academic_year')->unique();
            $table->date('startOfTheFirstSemester');
            $table->date('startOfTheSecondSemester')->nullable();
            $table->date('endOfTheFirstSemester')->nullable();
            $table->date('endOfTheSecondSemester')->nullable();
            $table->boolean('currentAcademic');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academics');
    }
};
