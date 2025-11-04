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
        Schema::table('users', function (Blueprint $table) {
            // Ajouter les champs manquants pour le profil
            if (!Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('position');
            }
            if (!Schema::hasColumn('users', 'location')) {
                $table->string('location')->nullable()->after('bio');
            }
            if (!Schema::hasColumn('users', 'website')) {
                $table->string('website')->nullable()->after('location');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bio', 'location', 'website']);
        });
    }
};

