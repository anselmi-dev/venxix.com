<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLocaleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    public function __invoke(UpdateLocaleRequest $request): RedirectResponse
    {
        $locale = $request->string('locale')->toString();

        Cookie::queue('locale', $locale, 60 * 24 * 365);

        $request->user()?->update(['locale' => $locale]);

        return back();
    }
}
