<?php

namespace JustChill\LaravelCaptcha\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CaptchaMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('post')) {
            $captcha = $request->input('captcha');
            
            if (!$captcha || !app('captcha')->validate($captcha)) {
                return back()->withErrors(['captcha' => 'Invalid CAPTCHA. Please try again.'])
                            ->withInput($request->except('captcha'));
            }
        }
        
        return $next($request);
    }
}
