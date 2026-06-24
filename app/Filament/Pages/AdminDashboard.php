<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AdminDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|\UnitEnum|null $navigationGroup = 'Operacion';

    protected static ?string $navigationLabel = 'Dashboard Administracion';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = '/';

    protected static string $view = 'filament.pages.admin-dashboard';
}
