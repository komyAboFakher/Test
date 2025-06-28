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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->string('schoolGraduatedFrom'); //school name such as al saade
            $table->string('photo')->nullable(); //student photo
            $table->decimal('Gpa', 5, 2)->check('Gpa >= 0 and Gpa <= 100')->default(0); //Student GPA
            $table->boolean('expelled')->default(false);
            $table->string('justification')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
