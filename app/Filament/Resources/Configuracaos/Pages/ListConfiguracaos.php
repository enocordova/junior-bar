<?php

namespace App\Filament\Resources\Configuracaos\Pages;

use App\Filament\Resources\Configuracaos\ConfiguracaoResource;
use App\Models\Configuracao;
use Filament\Actions\Action; // <--- Ação Genérica de Página (Funciona sempre)
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListConfiguracaos extends ListRecords
{
    protected static string $resource = ConfiguracaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('setup_wifi')
                ->label('Configurar Wi-Fi (QR Code)')
                ->icon('heroicon-o-wifi')
                ->color('primary')
                ->modalHeading('Dados do Wi-Fi')
                ->modalDescription('Estes dados serão usados para gerar o QR Code nas mesas.')
                ->modalSubmitActionLabel('Atualizar Wi-Fi')
                ->form([
                    TextInput::make('ssid')
                        ->label('Nome da Rede (SSID)')
                        ->required(),
                    
                    TextInput::make('senha')
                        ->label('Senha do Wi-Fi')
                        ->password()
                        ->revealable()
                        ->required(),
                ])
                ->fillForm(fn (): array => [
                    'ssid' => Configuracao::where('chave', 'wifi_ssid')->value('valor'),
                    'senha' => Configuracao::where('chave', 'wifi_senha')->value('valor'),
                ])
                ->action(function (array $data): void {
                    Configuracao::updateOrCreate(
                        ['chave' => 'wifi_ssid'],
                        ['valor' => $data['ssid'], 'descricao' => 'Nome da Rede Wi-Fi (SSID) para o QR Code']
                    );

                    Configuracao::updateOrCreate(
                        ['chave' => 'wifi_senha'],
                        ['valor' => $data['senha'], 'descricao' => 'Senha do Wi-Fi (QR Code)']
                    );

                    Notification::make()
                        ->title('Wi-Fi atualizado com sucesso!')
                        ->success()
                        ->send();
                }),
        ];
    }
}