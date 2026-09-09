<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Number;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        Number::useLocale($locale === 'es' ? 'es_UY' : 'en_US');

        if ($request->cookie('locale') !== $locale) {
            Cookie::queue('locale', $locale, 60 * 24 * 365);
        }

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        /** @var list<string> $supported */
        $supported = config('app.supported_locales');

        $userLocale = $request->user()?->locale;
        if (is_string($userLocale) && in_array($userLocale, $supported, true)) {
            return $userLocale;
        }

        $cookieLocale = $request->cookie('locale');
        if (is_string($cookieLocale) && in_array($cookieLocale, $supported, true)) {
            return $cookieLocale;
        }

        $preferred = $request->getPreferredLanguage($supported);

        return is_string($preferred) && in_array($preferred, $supported, true)
            ? $preferred
            : (string) config('app.locale');
    }
}
