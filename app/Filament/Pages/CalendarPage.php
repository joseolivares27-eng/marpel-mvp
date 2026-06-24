<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class CalendarPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'Operacion';

    protected static ?string $navigationLabel = 'Calendario';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.calendar-page';
}
