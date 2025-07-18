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
        Schema::create('reactables', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained();
            $table->foreignId('reaction_id')->constrained();
            $table->morphs('reactable');
            $table->primary(['user_id', 'reactable_id', 'reactable_type']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reactables');
    }
};
