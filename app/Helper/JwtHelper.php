<?php

namespace App\Helper;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Log;

trait JwtHelper
{
    
    /**
     * 
     *
     * @param  \App\Models\User  $user
     * @return array Associative array containing access_token, refresh_token, etc.
     */
    protected function generateTokensForUser(User $user): array
    {
        $issuedAt = now()->timestamp; 
        $accessExpireAt = $issuedAt + config('auth.jwt_access_token_ttl'); 
        $refreshExpireAt = $issuedAt + config('auth.jwt_refresh_token_ttl'); 

        // Access Token Payload
        $accessPayload = [
            'iss' => url('/'), // Issuer 
            'aud' => url('/'), // Audience 
            'iat' => $issuedAt, // Issued at 
            'nbf' => $issuedAt, // Not before 
            'exp' => $accessExpireAt, // Expiration time 
            'sub' => $user->id, // Subject 
            'type' => 'access', 

            'uid' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'sid' => $user->store_id, 
            'roles' => $user->getRoleNames()->toArray(), 
        ];
        $accessToken = JWT::encode($accessPayload, config('auth.jwt_secret_key'), config('auth.jwt_algo'));

        // Refresh Token Payload
        $refreshTokenId = Str::random(40);

        try {
             $user->refreshTokens()->create([
                'token' => $refreshTokenId, 
                'expires_at' => now()->addSeconds(config('auth.jwt_refresh_token_ttl')),
                'revoked' => false,
            ]);
        } catch (Exception $e) {
            Log::error("Failed to save refresh token for user {$user->id}: " . $e->getMessage());
        }


        $refreshPayload = [
            'iss' => url('/'), 'aud' => url('/'), 'iat' => $issuedAt, 'nbf' => $issuedAt,
            'exp' => $refreshExpireAt, 'sub' => $user->id, 'type' => 'refresh',
            'jti' => $refreshTokenId, // JWT ID
        ];
        $refreshToken = JWT::encode($refreshPayload, config('auth.jwt_secret_key'), config('auth.jwt_algo'));

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'access_expires_in' => config('auth.jwt_access_token_ttl'), 
            'refresh_expires_in' => config('auth.jwt_refresh_token_ttl'), 
        ];
    }

    /**
     * 
     *
     * @param  string  $token
     * @return object|null Decoded payload object or null on failure.
     */
    protected function decodeJwtToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key(config('auth.jwt_secret_key'), config('auth.jwt_algo')));
        } catch (Exception $e) {
            // Log the specific JWT decoding error for debugging
            Log::debug('JWT Helper: Failed to decode token.', [
                'error_message' => $e->getMessage(),
                'error_class' => get_class($e),
                'token_substring' => substr($token, 0, 20).'...'
            ]);
            return null;
        }
    }

    /**
     * 
     *
     * @param  \App\Models\User  $user
     * @param  string  $jti The JWT ID (token identifier) of the refresh token.
     * @return bool True on success, false on failure.
     */
    protected function revokeRefreshTokenByJti(User $user, string $jti): bool
    {
        try {
            $revokedCount = $user->refreshTokens()->where('token', $jti)->where('revoked', false)->update(['revoked' => true]);
            return $revokedCount > 0;
        } catch (Exception $e) {
            Log::error('JWT Helper: Failed to revoke refresh token by JTI.', [
                'user_id' => $user->id, 'jti' => $jti, 'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}