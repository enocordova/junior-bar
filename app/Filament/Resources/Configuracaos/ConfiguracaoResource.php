<?php

namespace App\Filament\Resources\Configuracaos;

use App\Filament\Resources\Configuracaos\Pages;
use App\Models\Configuracao;
use App\Filament\Resources\Configuracaos\Schemas\ConfiguracaoForm; // IMPORTANTE: Importando sua classe de formulário
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class ConfiguracaoResource extends Resource
{
    protected static ?string $model = Configuracao::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Configurações';
    protected static ?string $modelLabel = 'Configuração';
    protected static ?string $pluralModelLabel = 'Configurações';

    // Deixamos null para desativar a barra de pesquisa global também (Preservado)
    protected static ?string $recordTitleAttribute = null;

    public static function form(Schema $schema): Schema
    {
        // AQUI ESTÁ A MUDANÇA PRINCIPAL:
        // Usamos a classe dedicada que contém a lógica do Select (Porto/Natal)
        return ConfiguracaoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Filtro de segurança preservado
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNotIn('chave', ['wifi_ssid', 'wifi_senha']))
            ->columns([
                TextColumn::make('chave')
                    ->label('Configuração')
                    ->formatStateUsing(fn (string $state): string => Str::title(str_replace('_', ' ', $state)))
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('valor')
                    ->label('Valor Atual')
                    ->limit(40)
                    ->badge()
                    ->color(fn ($state, $record) => match($record->chave) {
                        'sistema_pais' => 'info',
                        default => 'success',
                    })
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->chave === 'sistema_pais') {
                            $countries = config('locales.countries', []);
                            return $countries[$state]['name'] ?? $state;
                        }
                        return $state;
                    })
                    ->copyable(),

                TextColumn::make('descricao')
                    ->label('Detalhes')
                    ->limit(50)
                    ->color('gray')
                    ->size('sm'),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->label('Alterar')
                    ->modalHeading('Editar Configuração')
                    ->modalWidth('lg'),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConfiguracaos::route('/'),
        ];
    }
}
