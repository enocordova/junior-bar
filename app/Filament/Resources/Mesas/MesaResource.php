<?php

namespace App\Filament\Resources\Mesas;

use App\Filament\Resources\Mesas\Pages;
use App\Models\Mesa;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema; // [FIX] Novo padrão v4
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

// [FIX] Actions agora vêm do namespace global unificado
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class MesaResource extends Resource
{
    protected static ?string $model = Mesa::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $recordTitleAttribute = 'numero';
    
    protected static ?string $navigationLabel = 'Mesas';

    // [FIX] Assinatura atualizada: Schema em vez de Form
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([ // [FIX] components() em vez de schema()
                TextInput::make('numero')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->unique(ignoreRecord: true)
                    ->label('Número da Mesa'),
                    
                TextInput::make('capacidade')
                    ->numeric()
                    ->required()
                    ->default(4)
                    ->label('Capacidade de Pessoas'),
                    
                TextInput::make('label')
                    ->label('Etiqueta (Opcional)')
                    ->placeholder('Ex: VIP')
                    ->maxLength(255),
                    
                Toggle::make('ativa')
                    ->required()
                    ->default(true)
                    ->label('Mesa Ativa/Disponível'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->formatStateUsing(fn ($state) => "Mesa {$state}"),
                    
                TextColumn::make('label')
                    ->searchable()
                    ->placeholder('-'),
                    
                TextColumn::make('capacidade')
                    ->sortable()
                    ->suffix(' lug.')
                    ->label('Capacidade'),
                    
                IconColumn::make('ativa')
                    ->boolean()
                    ->label('Ativa'),
                    
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Criada em'),
            ])
            ->defaultSort('numero')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMesas::route('/'),
            'create' => Pages\CreateMesa::route('/create'),
            'edit' => Pages\EditMesa::route('/{record}/edit'),
        ];
    }
}