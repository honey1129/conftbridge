<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;

class CheckForMaintenanceMode {
    protected $app;

    public function __construct(Application $app) {
        $this->app = $app;
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        try{
            if ($this->app->isDownForMaintenance() &&
                !in_array($request->ip(), ['1.193.58.121'])) {
                return response('网站维护中!'.$request->ip(), 503);
            }

            return $next($request);
        } catch (\Exception $e){
            \Log::info($e->getMessage().$e->getLine());
        }

    }
}
