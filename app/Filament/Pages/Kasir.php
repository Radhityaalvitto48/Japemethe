<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class Kasir extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $navigationLabel = 'Kasir';

    protected static ?string $title = 'Kasir PoS';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.kasir';

    public function mount(): void
    {
        $adminPath = trim((string) env('ADMIN_PATH', 'secure-panel-9x7k2'), '/');

        $this->redirect(url('/' . $adminPath . '/kasir-app'), navigate: true);
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user !== null && in_array($user->role, ['admin', 'kasir'], true);
    }
}
