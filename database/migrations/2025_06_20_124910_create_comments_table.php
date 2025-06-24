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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Self-referential relationship for reply on the comments !!
            $table->foreignId('parent_id')->nullable()->constrained('comments')->nullOnDelete(); 
            /*MAJD: !!!!!!!!!!!
            nullondelete is used just in case that the user who made the reply or comment is deleted, then his reply will still 
            exist on the posts and comments, while cascadeondelete will make the replies deleted if the user has been
            removed!!! */
            $table->text('content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
