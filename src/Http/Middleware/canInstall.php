<?php

namespace Bdc\Installer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class canInstall
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $installedLogFile = storage_path('installed');
        if (!file_exists($installedLogFile)) {
            if (!in_array($request->path(), ['install','install/configuration/validation','install/process','install/database','install/process-store'])) {
                return redirect('/install');
            }
        }
        return $next($request);
    }
}
