<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */

    'name' => env('APP_NAME', 'Junior Bar'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    */

    'timezone' => 'Europe/Lisbon',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    | AQUI ESTÁ A MUDANÇA PRINCIPAL: pt_PT
    */

    'locale' => 'pt',

    'fallback_locale' => 'pt',

    'faker_locale' => 'pt_PT',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    */

    'maintenance' => [
        'driver' => 'file',
        // 'store' => 'redis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    */

    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\Filament\AdminPanelProvider::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    */

    'aliases' => Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | KDS Socket Configuration (Custom)
    |--------------------------------------------------------------------------
    |
    | Aqui registamos as variáveis de ambiente que definem onde está o servidor
    | Node.js. Isto permite mudar IPs no .env sem mexer no código.
    |
    */

    // URL interna: Usada pelo Laravel (Backend) para enviar POSTs ao Node
    'node_internal_url' => env('NODE_INTERNAL_URL', 'http://socket:3000'),

    // URL pública: Usada pelo Javascript (Frontend) para conectar o WebSocket
    'node_public_url' => env('VITE_SOCKET_URL', 'http://localhost:3000'),

    // Segredo partilhado para autenticar broadcasts Laravel -> Node
    'broadcast_secret' => env('BROADCAST_SECRET', 'mudar-em-producao'),

];