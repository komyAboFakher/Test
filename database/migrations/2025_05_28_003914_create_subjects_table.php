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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->enum('subjectName',['physics','math','chemistry','history','biology','computer','english']);
            $table->integer('minMark');
            $table->integer('maxMark');
            $table->enum('grade',['1','2','3','4','5','6','7','8','9','10','11','12']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suject');
    }
};
