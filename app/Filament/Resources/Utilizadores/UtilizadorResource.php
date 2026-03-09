<?php

namespace App\Filament\Resources\Utilizadores;

use App\Filament\Resources\Utilizadores\Pages;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\Hash;

class UtilizadorResource extends Resource
{
    protected static ?int $navigationSort = 10;

    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Utilizadores';

    protected static ?string $modelLabel = 'Utilizador';
    protected static ?string $pluralModelLabel = 'Utilizadores';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->label('Nome')
                    ->maxLength(255),

                TextInput::make('email')
                    ->required()
                    ->label('Login (utilizador)')
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Select::make('role')
                    ->required()
                    ->label('Perfil')
                    ->options([
                        'admin'   => 'Admin / Gerente',
                        'garcom'  => 'Garçom',
                        'cozinha' => 'Cozinha',
                    ]),

                TextInput::make('password')
                    ->label('Senha')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->minLength(6)
                    ->placeholder(fn (string $operation) => $operation === 'edit' ? 'Deixar em branco para não alterar' : null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Login')
                    ->searchable(),

                TextColumn::make('role')
                    ->label('Perfil')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin'   => 'success',
                        'garcom'  => 'info',
                        'cozinha' => 'warning',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin'   => 'Admin / Gerente',
                        'garcom'  => 'Garçom',
                        'cozinha' => 'Cozinha',
                        default   => $state,
                    }),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('role')
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (User $record): bool => $record->id === auth()->id()),
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
            'index'  => Pages\ListUtilizadores::route('/'),
            'create' => Pages\CreateUtilizador::route('/create'),
            'edit'   => Pages\EditUtilizador::route('/{record}/edit'),
        ];
    }
}
