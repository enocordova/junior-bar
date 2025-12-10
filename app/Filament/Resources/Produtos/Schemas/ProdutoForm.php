<?php

namespace App\Filament\Resources\Produtos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProdutoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')
                    ->required(),
                TextInput::make('categoria')
                    ->required(),
                TextInput::make('preco')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('imagem')
                    ->default(null),
                Toggle::make('ativo')
                    ->required(),
            ]);
    }
}
