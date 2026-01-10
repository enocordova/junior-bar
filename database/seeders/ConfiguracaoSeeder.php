<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Configuracao;

class ConfiguracaoSeeder extends Seeder
{
    public function run(): void
    {
        // Lista de configurações padrões do sistema
        $configs = [
            [
                'chave' => 'impressora_cozinha_ip', 
                'valor' => '192.168.1.200', 
                'descricao' => 'IP Impressora Cozinha'
            ],
            [
                'chave' => 'impressora_bar_ip',     
                'valor' => '192.168.1.201', 
                'descricao' => 'IP Impressora Bar'
            ],
            [
                'chave' => 'wifi_senha',            
                'valor' => 'juniorbar2024', 
                'descricao' => 'Senha do Wi-Fi Clientes'
            ],
            [
                'chave' => 'taxa_servico',          
                'valor' => '10',            
                'descricao' => 'Taxa de Serviço (%)'
            ],
        ];

        // Cria ou atualiza (se já existir, não duplica)
        foreach ($configs as $conf) {
            Configuracao::updateOrCreate(['chave' => $conf['chave']], $conf);
        }
    }
}