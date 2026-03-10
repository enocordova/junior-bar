<?php

namespace App\Filament\Resources\Produtos;

use App\Filament\Resources\Produtos\Pages;
use App\Models\Configuracao;
use App\Models\Produto;
use Filament\Forms\Components\Select;
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
    protected static ?int $navigationSort = 1;
    
    protected static ?string $model = Produto::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'nome';
    
    protected static ?string $navigationLabel = 'Produtos';

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
                    
                Select::make('categoria')
                    ->required()
                    ->label('Categoria')
                    ->options([
                        'Espetinhos'      => 'Espetinhos',
                        'Bebidas'         => 'Bebidas',
                        'Porções'         => 'Porções',
                        'Caldos'          => 'Caldos',
                        'Sucos'           => 'Sucos',
                        'Lanches'         => 'Lanches',
                        'Acompanhamentos' => 'Acompanhamentos',
                    ]),
                    
                TextInput::make('preco')
                    ->required()
                    ->numeric()
                    ->prefix(Configuracao::countryPreset()['currency_symbol'])
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
                TextColumn::make('nome')->weight('bold'),
                TextColumn::make('categoria')->badge(),
                TextColumn::make('preco')->money(Configuracao::countryPreset()['currency_code'])->sortable(),
                IconColumn::make('ativo')->boolean()->label('Ativo'),
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