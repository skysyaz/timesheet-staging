<?php

namespace App\Auth\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as Responsable;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        // Do not chain ->header() here: Livewire login returns a Redirector, not
        // RedirectResponse, and calling header() causes a 500 after successful auth.
        return redirect()->intended(Filament::getUrl());
    }
}
