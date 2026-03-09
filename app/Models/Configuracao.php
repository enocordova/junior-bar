<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Configuracao extends Model
{
    protected $table = 'configuracoes';

    protected $fillable = [
        'grupo',
        'titulo',
        'chave',
        'valor',
        'tipo',
        'descricao'
    ];

    /**
     * Helper: busca valor de uma config com cache (1h).
     */
    public static function valor(string $chave, string $default = ''): string
    {
        return Cache::remember("config_{$chave}", 3600, function () use ($chave, $default) {
            return static::where('chave', $chave)->value('valor') ?? $default;
        });
    }

    /**
     * Helper: retorna o preset do país selecionado.
     */
    public static function countryPreset(): array
    {
        $pais = static::valor('sistema_pais', config('locales.default', 'PT'));
        $countries = config('locales.countries', []);

        return $countries[$pais] ?? $countries[config('locales.default', 'PT')] ?? [
            'name'            => '🇵🇹 Portugal',
            'locale'          => 'pt',
            'timezone'        => 'Europe/Lisbon',
            'currency_symbol' => '€',
            'currency_code'   => 'EUR',
            'currency_before' => true,
            'decimal_sep'     => ',',
            'thousands_sep'   => '.',
            'date_locale'     => 'pt-PT',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Configuracao $configuracao) {
            Cache::forget("config_{$configuracao->chave}");

            // Limpar caches relacionados ao país/timezone/locale
            if ($configuracao->chave === 'sistema_pais') {
                Cache::forget('app_timezone');
                Cache::forget('app_country_preset');
            }

            // Retrocompatibilidade
            if ($configuracao->chave === 'sistema_timezone') {
                Cache::forget('app_timezone');
            }
        });
    }
}
