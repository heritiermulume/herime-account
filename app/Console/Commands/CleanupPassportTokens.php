<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupPassportTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:cleanup {--days=7 : Nombre de jours avant suppression des tokens révoqués}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nettoie les tokens Passport révoqués et expirés pour améliorer les performances';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        
        $this->info("🧹 Nettoyage des tokens Passport...");
        $this->newLine();
        
        // Statistiques avant nettoyage
        $totalBefore = DB::table('oauth_access_tokens')->count();
        $revokedBefore = DB::table('oauth_access_tokens')->where('revoked', true)->count();
        $activeBefore = $totalBefore - $revokedBefore;
        
        $this->line("📊 Statistiques avant nettoyage:");
        $this->line("  • Tokens totaux: {$totalBefore}");
        $this->line("  • Tokens actifs: {$activeBefore}");
        $this->line("  • Tokens révoqués: {$revokedBefore}");
        $this->newLine();
        
        // Supprimer les tokens révoqués anciens
        $cutoffDate = now()->subDays($days);
        $this->info("🗑️  Suppression des tokens révoqués de plus de {$days} jours...");
        
        $deleted = DB::table('oauth_access_tokens')
            ->where('revoked', true)
            ->where('created_at', '<', $cutoffDate)
            ->delete();
        
        $this->line("  ✅ {$deleted} tokens révoqués supprimés");
        $this->newLine();
        
        // Supprimer les tokens expirés
        $this->info("🗑️  Suppression des tokens expirés...");
        
        $expiredDeleted = DB::table('oauth_access_tokens')
            ->where('expires_at', '<', now())
            ->delete();
        
        $this->line("  ✅ {$expiredDeleted} tokens expirés supprimés");
        $this->newLine();
        
        // Optimiser la table
        $this->info("⚡ Optimisation de la table oauth_access_tokens...");
        
        try {
            DB::statement('OPTIMIZE TABLE oauth_access_tokens');
            $this->line("  ✅ Table optimisée");
        } catch (\Exception $e) {
            $this->warn("  ⚠️  Impossible d'optimiser la table: " . $e->getMessage());
        }
        
        $this->newLine();
        
        // Statistiques après nettoyage
        $totalAfter = DB::table('oauth_access_tokens')->count();
        $revokedAfter = DB::table('oauth_access_tokens')->where('revoked', true)->count();
        $activeAfter = $totalAfter - $revokedAfter;
        
        $totalDeleted = $deleted + $expiredDeleted;
        $savedSpace = round(($totalDeleted * 1024) / 1024, 2); // Estimation en MB
        
        $this->info("📊 Résultat du nettoyage:");
        $this->line("  • Tokens supprimés: {$totalDeleted}");
        $this->line("  • Tokens restants: {$totalAfter}");
        $this->line("  • Tokens actifs: {$activeAfter}");
        $this->line("  • Tokens révoqués: {$revokedAfter}");
        $this->line("  • Espace libéré (estimé): {$savedSpace} MB");
        $this->newLine();
        
        if ($totalDeleted > 0) {
            $this->info("✅ Nettoyage terminé avec succès!");
            
            if ($revokedAfter > 100) {
                $this->newLine();
                $this->warn("⚠️  Il reste {$revokedAfter} tokens révoqués de moins de {$days} jours.");
                $this->line("   Pour un nettoyage plus agressif, utilisez: php artisan passport:cleanup --days=1");
            }
        } else {
            $this->info("✅ Aucun token à nettoyer. La base est déjà propre!");
        }
        
        return 0;
    }
}

