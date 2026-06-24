<?php

namespace App\Filament\Resources\IntegrationSources;

use App\Filament\Resources\IntegrationSources\Pages\ManageIntegrationSources;
use App\Models\IntegrationSource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IntegrationSourceResource extends Resource
{
    protected static ?string $model = IntegrationSource::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static string|\UnitEnum|null $navigationGroup = 'Automatizaciones';

    protected static ?string $modelLabel = 'canal de integracion';

    protected static ?string $pluralModelLabel = 'canales de integracion';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nombre')->required(),
            TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true),
            Select::make('type')->label('Tipo')->options([
                'messaging' => 'Mensajeria',
                'assistant' => 'Asistente',
                'form' => 'Formulario',
                'email' => 'Correo',
                'api' => 'API',
                'external' => 'Externo',
            ])->default('external')->required(),
            Toggle::make('is_active')->label('Activo'),
            Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Canal')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->searchable(),
                TextColumn::make('type')->label('Tipo')->badge()->sortable(),
                IconColumn::make('is_active')->label('Activo')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageIntegrationSources::route('/'),
        ];
    }
}
