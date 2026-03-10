<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // firstOrCreate garante idempotência: seguro rodar várias vezes
        $admin = User::firstOrCreate(
            ['email' => env('SEED_ADMIN_EMAIL', 'admin')],
            ['name' => 'Gerente Admin', 'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'mudar-em-producao'))]
        );
        $admin->forceFill(['role' => 'admin'])->save();

        $garcom = User::firstOrCreate(
            ['email' => env('SEED_GARCOM_EMAIL', 'garcom')],
            ['name' => 'Garçom', 'password' => Hash::make(env('SEED_GARCOM_PASSWORD', 'mudar-em-producao'))]
        );
        $garcom->forceFill(['role' => 'garcom'])->save();

        $cozinha = User::firstOrCreate(
            ['email' => env('SEED_COZINHA_EMAIL', 'cozinha')],
            ['name' => 'Cozinha', 'password' => Hash::make(env('SEED_COZINHA_PASSWORD', 'mudar-em-producao'))]
        );
        $cozinha->forceFill(['role' => 'cozinha'])->save();

        $this->call(ProdutoSeeder::class);
        $this->call(ConfiguracaoSeeder::class);
    }
}
