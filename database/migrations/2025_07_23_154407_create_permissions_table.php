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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->enum('permission',['Library','Nurse','Oversee']); // add permission here
            $table->text('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Temporarily disable foreign key checks
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('permissions');

        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();
    }
};
