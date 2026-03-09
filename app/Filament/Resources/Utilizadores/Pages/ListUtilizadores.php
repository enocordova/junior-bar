<?php

namespace App\Filament\Resources\Utilizadores\Pages;

use App\Filament\Resources\Utilizadores\UtilizadorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUtilizadores extends ListRecords
{
    protected static string $resource = UtilizadorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novo Utilizador'),
        ];
    }
}
