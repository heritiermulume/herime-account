<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Créer un super utilisateur administrateur
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrateur',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 'super_user',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Super utilisateur créé avec succès !');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Mot de passe: password');

        // Seed les administrateurs
        $this->call(AdminSeeder::class);
    }
}
