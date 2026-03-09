<?php

namespace App\Filament\Resources\Utilizadores\Pages;

use App\Filament\Resources\Utilizadores\UtilizadorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditUtilizador extends EditRecord
{
    protected static string $resource = UtilizadorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn () => $this->record->id === Auth::id()),
        ];
    }
}
