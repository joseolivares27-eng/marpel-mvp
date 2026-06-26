<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ManagementDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Gerencia';

    protected static ?string $navigationLabel = 'Dashboard Gerencia';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.management-dashboard';
}
