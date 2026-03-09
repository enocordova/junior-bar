<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\App;
use App\Models\Configuracao;
use Symfony\Component\HttpFoundation\Response;

class ConfigurarTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        // Recuperar preset do país com cache de 1h
        $preset = Cache::remember('app_country_preset', 3600, function () {
            return Configuracao::countryPreset();
        });

        // Aplicar timezone
        $timezone = $preset['timezone'] ?? 'Europe/Lisbon';
        if (in_array($timezone, timezone_identifiers_list())) {
            Config::set('app.timezone', $timezone);
            date_default_timezone_set($timezone);
        }

        // Aplicar locale
        $locale = $preset['locale'] ?? 'pt';
        App::setLocale($locale);

        // Disponibilizar preset completo para as views via Config
        Config::set('app.country_preset', $preset);

        return $next($request);
    }
}
