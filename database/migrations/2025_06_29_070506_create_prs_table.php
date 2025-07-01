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
        Schema::create('prs', function (Blueprint $table) {
            $table->id();
            // Publisher (PR Officer)
            $table->foreignId('publisher_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['trip', 'sports', 'cultural', 'academic']);
            $table->text('description');
            $table->date('date');
            $table->float('cost_per_student');
            $table->boolean('dean_approval')->nullable();
            $table->text('dean_rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prs');
    }
};
