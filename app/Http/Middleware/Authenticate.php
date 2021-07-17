<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Auth\AuthenticationException;

use Illuminate\Support\Arr;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request, array $guards)
    {
        if (! $request->expectsJson()) {
            switch (current($guards)) {
                case 'admin-web':
                    return route('admin.login');
                
                default:
                    return route('login');
            }
        }
    }

    protected function unauthenticated($request, array $guards)
{
    throw new AuthenticationException(
        'Unauthenticated.', $guards, $this->redirectTo($request, $guards)
    );
}
}
