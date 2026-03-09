<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Configuracao;

class ConfiguracaoSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            // ═══════════════════════════════════════
            // IDENTIDADE
            // ═══════════════════════════════════════
            [
                'grupo'     => 'Identidade',
                'titulo'    => 'Nome do Restaurante',
                'chave'     => 'nome_restaurante',
                'valor'     => 'Junior BAR',
                'tipo'      => 'text',
                'descricao' => 'Aparece no cabeçalho de todas as telas e nas impressões.',
            ],

            // ═══════════════════════════════════════
            // COZINHA (KDS)
            // ═══════════════════════════════════════
            [
                'grupo'     => 'Cozinha',
                'titulo'    => 'Alerta Amarelo (min)',
                'chave'     => 'tempo_alerta_amarelo',
                'valor'     => '10',
                'tipo'      => 'number',
                'descricao' => 'Minutos até o timer do pedido ficar amarelo (atenção).',
            ],
            [
                'grupo'     => 'Cozinha',
                'titulo'    => 'Alerta Vermelho (min)',
                'chave'     => 'tempo_alerta_vermelho',
                'valor'     => '20',
                'tipo'      => 'number',
                'descricao' => 'Minutos até o timer do pedido ficar vermelho (atrasado).',
            ],

            // ═══════════════════════════════════════
            // CLIENTE
            // ═══════════════════════════════════════
            [
                'grupo'     => 'Cliente',
                'titulo'    => 'Nome da Rede Wi-Fi',
                'chave'     => 'wifi_ssid',
                'valor'     => env('WIFI_SSID', 'MeuWiFi'),
                'tipo'      => 'text',
                'descricao' => 'Nome da rede Wi-Fi mostrado no QR Code do garçom.',
            ],
            [
                'grupo'     => 'Cliente',
                'titulo'    => 'Senha do Wi-Fi',
                'chave'     => 'wifi_senha',
                'valor'     => env('WIFI_PASSWORD', 'alterar-no-painel'),
                'tipo'      => 'password',
                'descricao' => 'Senha que aparece no QR Code para o cliente.',
            ],

            // ═══════════════════════════════════════
            // SISTEMA
            // ═══════════════════════════════════════
            [
                'grupo'     => 'Sistema',
                'titulo'    => 'País / Localização',
                'chave'     => 'sistema_pais',
                'valor'     => 'PT',
                'tipo'      => 'select',
                'descricao' => 'Define o idioma, moeda e fuso horário do sistema.',
            ],
        ];

        foreach ($configs as $conf) {
            Configuracao::updateOrCreate(
                ['chave' => $conf['chave']],
                $conf
            );
        }

        // Remover configs antigas que não são usadas
        Configuracao::whereIn('chave', [
            'socket_url',
            'impressora_cozinha_ip',
            'impressora_bar_ip',
            'taxa_servico',
            'sistema_timezone', // Substituído por sistema_pais
        ])->delete();
    }
}
