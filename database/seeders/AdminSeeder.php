<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the admins database.
     */
    public function run(): void
    {
        // Créer un super admin dans la table admins
        $admin = Admin::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrateur',
                'email' => 'admin@example.com',
                'password' => Hash::make('Herime2024!'),
                'role' => 'super_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Créer/mettre à jour aussi un utilisateur classique lié avec le même email
        // pour permettre la connexion via l'API /login qui utilise le modèle User
        $user = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrateur',
                'email' => 'admin@example.com',
                'password' => Hash::make('Herime2024!'),
                'phone' => $admin->phone ?? '0000000000',
                'gender' => 'autre',
                'birthdate' => now()->subYears(30)->toDateString(),
                'company' => 'Herime',
                'position' => 'Super Admin',
                'role' => 'super_user',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin créé avec succès !');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Mot de passe: Herime2024!');
    }
}

