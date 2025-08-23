<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        // database/migrations/xxxx_xx_xx_xxxxxx_add_verification_token_to_users_table.php

        public function up(): void
        {
            Schema::table('users', function (Blueprint $table) {
                $table->string('verification_token')->nullable()->after('remember_token');
                $table->timestamp('verification_token_expires_at')->nullable()->after('verification_token');
            });
        }

        public function down(): void
        {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['verification_token', 'verification_token_expires_at']);
            });
        }
};
