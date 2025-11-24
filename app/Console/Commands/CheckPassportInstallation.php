<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckPassportInstallation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie et diagnostique l\'installation de Laravel Passport';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Vérification de l\'installation de Laravel Passport...');
        $this->newLine();

        $hasErrors = false;

        // Vérifier les tables Passport
        $tables = [
            'oauth_clients',
            'oauth_personal_access_clients',
            'oauth_access_tokens',
            'oauth_refresh_tokens',
            'oauth_auth_codes',
        ];

        $this->info('📊 Vérification des tables de base de données:');
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $this->line("  ✅ Table '{$table}' existe ({$count} enregistrements)");
            } else {
                $this->error("  ❌ Table '{$table}' manquante!");
                $hasErrors = true;
            }
        }
        $this->newLine();

        // Vérifier les clés de chiffrement
        $this->info('🔐 Vérification des clés de chiffrement:');
        $privateKeyPath = storage_path('oauth-private.key');
        $publicKeyPath = storage_path('oauth-public.key');

        if (file_exists($privateKeyPath)) {
            $this->line("  ✅ Clé privée existe: {$privateKeyPath}");
        } else {
            $this->error("  ❌ Clé privée manquante: {$privateKeyPath}");
            $hasErrors = true;
        }

        if (file_exists($publicKeyPath)) {
            $this->line("  ✅ Clé publique existe: {$publicKeyPath}");
        } else {
            $this->error("  ❌ Clé publique manquante: {$publicKeyPath}");
            $hasErrors = true;
        }
        $this->newLine();

        // Vérifier les clients OAuth
        if (Schema::hasTable('oauth_clients')) {
            $clients = DB::table('oauth_clients')->get();
            $this->info('👥 Clients OAuth configurés:');
            if ($clients->isEmpty()) {
                $this->warn('  ⚠️  Aucun client OAuth configuré');
            } else {
                foreach ($clients as $client) {
                    $this->line("  • {$client->name} (ID: {$client->id}) - " . ($client->revoked ? 'RÉVOQUÉ' : 'ACTIF'));
                }
            }
            $this->newLine();
        }

        // Vérifier les Personal Access Clients
        if (Schema::hasTable('oauth_personal_access_clients')) {
            $personalClients = DB::table('oauth_personal_access_clients')->count();
            if ($personalClients > 0) {
                $this->line("✅ {$personalClients} Personal Access Client(s) configuré(s)");
            } else {
                $this->error('❌ Aucun Personal Access Client configuré');
                $this->warn('   Exécutez: php artisan passport:install');
                $hasErrors = true;
            }
            $this->newLine();
        }

        // Résumé
        if ($hasErrors) {
            $this->newLine();
            $this->error('❌ Des problèmes ont été détectés avec Laravel Passport!');
            $this->newLine();
            $this->warn('🔧 Pour réparer:');
            $this->line('  1. Exécutez les migrations: php artisan migrate');
            $this->line('  2. Installez Passport: php artisan passport:install');
            $this->line('  3. Vérifiez à nouveau: php artisan passport:check');
            return 1;
        }

        $this->newLine();
        $this->info('✅ Laravel Passport est correctement installé et configuré!');
        return 0;
    }
}

