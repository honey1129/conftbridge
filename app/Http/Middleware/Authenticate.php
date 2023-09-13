<?php

namespace App\Http\Middleware;

use Closure;
use App\User;
use App\Models\UserConfig;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\Log;

class Authenticate
{
    /**
     * The authentication factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param \Illuminate\Contracts\Auth\Factory $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string[] ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next)
    {
        try {
            if ($this->auth->guard('api')->check()) {
                $user = $this->auth->guard('api')->user();
                if (!$user) {
                    return response()->json([
                        'code' => 401,
                        'data' => [],
                        'msg'  => __($request->header('locale') . '.请先登录', [], $request->header('locale'))
                    ]);
                }
                if ($user->stoped == User::DISABLED_USER_STATUS) {
                    return response()->json([
                        'code' => 500,
                        'data' => [],
                        'msg'  => __($request->header('locale') . '.你已被冻结', [], $request->header('locale'))
                    ]);
                }
                $config = UserConfig::where('uid', $user->id)->first();
                $request->user = $user;
                $request->user_config = $config;
                return $next($request);
            } else {
                return response()->json([
                    'code' => 401,
                    'data' => [],
                    'msg'  => __($request->header('locale') . '.请先登录', [], $request->header('locale'))
                ]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return response()->json([
                'code' => 401,
                'data' => [],
                'msg'  => __($request->header('locale') . '.请先登录', [], $request->header('locale'))
            ]);
        }

    }
}