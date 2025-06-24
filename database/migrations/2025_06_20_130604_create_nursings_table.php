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
        Schema::create('nursings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete(); // Explicit student relationship
            $table->foreignId('nurse_id')->constrained('users')->cascadeOnDelete(); // Who recorded the entry
            $table->date('record_date')->default(now()); // Date of medical event
            $table->enum('record_type', ['checkup', 'incident', 'medication', 'vaccination', 'allergy', 'chronic_condition']);
            $table->text('description')->nullable(); 
            $table->text('treatment')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('follow_up')->default(false);
            $table->date('follow_up_date')->nullable();
            $table->enum('severity',['low', 'medium', 'high', 'extreme']); 
            $table->softDeletes(); // For record archiving
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nursings');
    }
};
