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
        Schema::table('user_sessions', function (Blueprint $table) {
            // Ajouter la colonne token_id pour lier la session au token Passport
            $table->string('token_id', 100)->nullable()->after('session_id');
            $table->index('token_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_sessions', function (Blueprint $table) {
            $table->dropIndex(['token_id']);
            $table->dropColumn('token_id');
        });
    }
};
