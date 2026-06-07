<?php

namespace App\Http\Middleware;

use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;
use Auth0\SDK\Exception\InvalidTokenException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Auth0Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $auth0 = new Auth0(new SdkConfiguration(
                strategy: SdkConfiguration::STRATEGY_API,
                domain: config('auth0.domain'),
                clientId: config('auth0.client_id'),
                clientSecret: config('auth0.client_secret'),
                audience: [config('auth0.audience')],
            ));

            $auth0->decode($token);
        } catch (InvalidTokenException $e) {
            return response()->json(['message' => 'Invalid token: ' . $e->getMessage()], 401);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Authentication failed.'], 401);
        }

        return $next($request);
    }
}
