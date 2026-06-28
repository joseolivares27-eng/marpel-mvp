<?php

namespace App\Filament\Pages;

use App\Services\GoogleCalendarService;
use Filament\Pages\Page;

class GoogleCalendarSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static string|\UnitEnum|null $navigationGroup = 'Automatizaciones';

    protected static ?string $navigationLabel = 'Google Calendar';

    protected static ?string $slug = 'google-calendar-settings';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.google-calendar-settings';

    public function getIsConnected(): bool
    {
        return app(GoogleCalendarService::class)->isConnected();
    }
}
