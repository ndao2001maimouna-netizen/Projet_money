<?php

namespace Database\Seeders;

use App\Models\Utilisateur;
use App\Models\Portefeuille;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Utilisateur::create([
            'nom' => 'Ndao',
            'prenom' => 'Maimouna',
            'telephone' => '771234567',
            'code_secret' => Hash::make('1234'),
            'role' => 'admin',
            'createur_id' => null,
        ]);

        Portefeuille::create([
            'utilisateur_id' => $admin->id,
            'solde' => 50000000000.00,
            'devise' => 'XOF',
        ]);
    }
}
