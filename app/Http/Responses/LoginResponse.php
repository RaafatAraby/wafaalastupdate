<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = auth()->user();

        if ($user && property_exists($user, 'is_active') && ! $user->is_active) {
            auth()->logout();

            return redirect()->route('filament.admin.auth.login')
                ->withErrors(['email' => 'الحساب غير مفعل، راجع مدير النظام.']);
        }

        return redirect()->intended(route('filament.admin.pages.dashboard'));
    }
}
