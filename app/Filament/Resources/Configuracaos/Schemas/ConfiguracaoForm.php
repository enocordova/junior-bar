<?php

namespace App\Filament\Resources\Configuracaos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class ConfiguracaoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Campo de Descrição (Apenas informativo)
                TextInput::make('descricao')
                    ->label('Configuração')
                    ->disabled()
                    ->dehydrated(false) // Não envia este campo ao salvar
                    ->columnSpanFull(),

                // LÓGICA PARA PAÍS / LOCALIZAÇÃO
                Select::make('valor')
                    ->label('Selecionar País / Localização')
                    ->options(fn () => collect(config('locales.countries', []))
                        ->mapWithKeys(fn ($preset, $code) => [$code => $preset['name']]))
                    ->default(config('locales.default', 'PT'))
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->required()
                    ->visible(fn ($record) => $record?->chave === 'sistema_pais'),

                // LÓGICA PARA OUTROS CAMPOS
                // Ex: Nome do Bar, Senha do Wi-Fi, etc.
                TextInput::make('valor')
                    ->label('Definir Valor')
                    ->required()
                    ->placeholder('Digite a informação aqui...')
                    ->visible(fn ($record) => $record?->chave !== 'sistema_pais'),

                // Campo Chave (Oculto para segurança)
                TextInput::make('chave')
                    ->hidden()
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }
}
