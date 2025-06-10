<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helper\JWTToken;
class JWTtokenVarificationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Retrieve token from Authorization header
        $authHeader = $request->header('Authorization');
        $decoded = JWTToken::verifyToken($authHeader);

        // If verification fails, return unauthorized response
        if ($decoded === 'unauthorized') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 401);
        }

        // Attach decoded token data to request headers for further use in controllers
        $request->headers->set('userEmail', $decoded->userEmail);
        $request->headers->set('userID', $decoded->userID);

        // Proceed with the request
        return $next($request);

    }
}
