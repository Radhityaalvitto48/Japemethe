<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;

abstract class BaseAdminResource extends Resource
{
    protected static function isAdminUser(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return (string) $user->role === 'admin';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::isAdminUser();
    }

    public static function canViewAny(): bool
    {
        return static::isAdminUser();
    }
}
