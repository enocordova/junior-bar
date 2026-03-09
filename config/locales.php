<?php

/**
 * Country presets for i18n.
 * Each country maps to: locale, timezone, currency symbol, formatting options.
 */
return [
    'countries' => [
        'PT' => [
            'name'            => '🇵🇹 Portugal',
            'locale'          => 'pt',
            'timezone'        => 'Europe/Lisbon',
            'currency_symbol' => '€',
            'currency_code'   => 'EUR',
            'currency_before' => true,
            'decimal_sep'     => ',',
            'thousands_sep'   => '.',
            'date_locale'     => 'pt-PT',
        ],
        'BR' => [
            'name'            => '🇧🇷 Brasil',
            'locale'          => 'pt_BR',
            'timezone'        => 'America/Sao_Paulo',
            'currency_symbol' => 'R$',
            'currency_code'   => 'BRL',
            'currency_before' => true,
            'decimal_sep'     => ',',
            'thousands_sep'   => '.',
            'date_locale'     => 'pt-BR',
        ],
    ],

    'default' => 'PT',
];
