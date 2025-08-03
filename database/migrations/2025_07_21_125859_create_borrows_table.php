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
        Schema::create('borrows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('book_id')->constrained('libraries')->onDelete('cascade');
            $table->string('serrial_number');
            $table->enum('borrow_status', ['pending', 'accepted', 'rejected'])->default('pending');

            //////////////////////////////////////////////////////////////////////////////
            $table->date('borrow_date');
            $table->date('due_date')->nullable();  //'retrieve_date'
            $table->date('returned_date')->nullable(); //actual return date
            $table->enum('book_status', ['borrowed', 'returned', 'overdue', 'lost'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrows');
    }
};
