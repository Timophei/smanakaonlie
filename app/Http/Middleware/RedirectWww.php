<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * www.shamanka.online → shamanka.online с тем же путём: у поисковиков один адрес сайта.
 */
class RedirectWww
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        if (str_starts_with($host, 'www.')) {
            return redirect()->to('https://'.substr($host, 4).$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
