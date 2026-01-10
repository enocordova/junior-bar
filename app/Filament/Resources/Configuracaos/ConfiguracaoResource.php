<?php

namespace App\Filament\Resources\Configuracaos;

use App\Filament\Resources\Configuracaos\Pages;
use App\Models\Configuracao;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema; 
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Illuminate\Database\Eloquent\Model;

class ConfiguracaoResource extends Resource
{
    protected static ?string $model = Configuracao::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    // Rótulo no Menu Lateral
    protected static ?string $navigationLabel = 'Configurações';

    // [IMPORTANTE] Rótulo Singular (ex: Botão "Nova Configuração")
    protected static ?string $modelLabel = 'Configuração';

    // [IMPORTANTE] Rótulo Plural (ex: Título "Configurações")
    protected static ?string $pluralModelLabel = 'Configurações';

    protected static ?string $recordTitleAttribute = 'descricao';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->label('Descrição')
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),

                TextInput::make('valor')
                    ->label('Valor')
                    ->required()
                    ->columnSpanFull(),

                TextInput::make('chave')
                    ->hidden()
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('descricao')
                    ->label('Item')
                    ->weight('bold')
                    ->searchable()
                    ->description(fn (Configuracao $record): string => $record->chave),

                TextColumn::make('valor')
                    ->label('Valor Atual')
                    ->limit(50),
            ])
            ->actions([
                // Ação de edição (se necessária no futuro)
                // EditAction::make(),
            ])
            ->paginated(false);
    }

    public static function canCreate(): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConfiguracaos::route('/'),
            'edit' => Pages\EditConfiguracao::route('/{record}/edit'),
        ];
    }
}