<?php

namespace App\Filament\Resources\Produtos;

use App\Filament\Resources\Produtos\Pages;
use App\Models\Produto;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class ProdutoResource extends Resource
{
    protected static ?string $model = Produto::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'nome';
    
    protected static ?string $navigationLabel = 'Produtos';

    // [IMPORTANTE] Traduções do Modelo
    protected static ?string $modelLabel = 'Produto';
    protected static ?string $pluralModelLabel = 'Produtos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')
                    ->required()
                    ->label('Nome do Produto')
                    ->maxLength(255),
                    
                TextInput::make('categoria')
                    ->required()
                    ->placeholder('Ex: Lanches, Bebidas')
                    ->label('Categoria')
                    ->maxLength(255),
                    
                TextInput::make('preco')
                    ->required()
                    ->numeric()
                    ->prefix('€')
                    ->default(0.00)
                    ->label('Preço'),
                    
                TextInput::make('imagem')
                    ->label('URL da Imagem')
                    ->url()
                    ->placeholder('https://...')
                    ->default(null),
                    
                Toggle::make('ativo')
                    ->required()
                    ->default(true)
                    ->label('Disponível para Venda'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome')->searchable()->weight('bold'),
                TextColumn::make('categoria')->searchable()->badge(),
                TextColumn::make('preco')->money('EUR')->sortable(),
                IconColumn::make('ativo')->boolean()->label('Ativo'),
                TextColumn::make('created_at')->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('categoria')
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
            'index' => Pages\ListProdutos::route('/'),
            'create' => Pages\CreateProduto::route('/create'),
            'edit' => Pages\EditProduto::route('/{record}/edit'),
        ];
    }
}