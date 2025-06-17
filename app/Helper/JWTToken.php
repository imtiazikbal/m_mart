<?php

namespace App\Helper;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Log;

class JWTToken
{
    /**
     * Generates a JWT for user authentication.
     *
     * @param string $userEmail
     * @param int $userID
     * @return string Encoded JWT
     */
    public static function createToken(string $userEmail, int $userID): string {
        $key = env('JWT_TOKEN', 'secret');
        $payload = [
            'iss' => 'laravel-app',  // Issuer of the token
            'iat' => time(),         // Issued at
            'exp' => time() + 60 * 60 * 24 * 30, // Expiration time (1 month)
            'userEmail' => $userEmail,
            'userID' => $userID
        ];
        return JWT::encode($payload, $key, 'HS256');
    }

    /**
     * Generates a short-lived JWT specifically for password reset.
     *
     * @param string $userEmail
     * @return string Encoded JWT
     */
    public static function createTokenForSetPassword(string $userEmail): string {
        $key = env('JWT_TOKEN', 'secret');
        $payload = [
            'iss' => 'laravel-app',
            'iat' => time(),
            'exp' => time() + 60 * 60 * 24 * 30, // Expiration time (1 month)
            'userEmail' => $userEmail,
            'userID' => '' // No user ID needed for password reset
        ];
        return JWT::encode($payload, $key, 'HS256');
    }

    /**
     * Verifies and decodes a JWT from the Authorization header.
     *
     * @param string|null $authHeader Authorization header with Bearer token
     * @return object|string Decoded token payload or 'unauthorized'
     */
    public static function verifyToken(?string $authHeader) {
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return 'unauthorized';
        }

        try {
            $token = $matches[1]; // Extract the token after 'Bearer'
            $key = env('JWT_TOKEN', 'secret');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));

            // Ensure payload contains necessary data
            if (isset($decoded->userEmail, $decoded->userID)) {
                return $decoded;
            }

            Log::warning('JWT verification failed: missing required claims.', ['token' => $token]);
            return 'unauthorized';

        } catch (Exception $exception) {
            Log::error('JWT verification error: ' . $exception->getMessage());
            return 'unauthorized';
        }
    }

    // admin varify token
    public static function adminTokenVarification($token){
        try {
            $key = env('JWT_TOKEN', 'secret');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));

            // Ensure payload contains necessary data
            if (isset($decoded->userEmail, $decoded->userID)) {
                return $decoded;
            }

            Log::warning('JWT verification failed: missing required claims.', ['token' => $token]);
            return 'unauthorized';

        } catch (Exception $exception) {
            Log::error('JWT verification error: ' . $exception->getMessage());
            return 'unauthorized';
        }
    }

}
