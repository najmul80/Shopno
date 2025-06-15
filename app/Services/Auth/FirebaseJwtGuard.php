<?php

namespace App\Services\Auth;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use UnexpectedValueException;
use Exception;
use Illuminate\Support\Facades\Log;

class FirebaseJwtGuard implements Guard
{
    use GuardHelpers; // setUser, getUser, hasUser, etc. 

    protected $request;
    // protected $provider; /
    protected $decodedToken;

    /**
     * Create a new authentication guard.
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(UserProvider $provider, Request $request)
    {
        $this->provider = $provider;
        $this->request = $request;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {

        if (!is_null($this->user)) {
            return $this->user;
        }

        $token = $this->getTokenForRequest();

        if (empty($token)) {
            return $this->user = null;
        }

        try {
            // config/auth.php 
            $this->decodedToken = JWT::decode(
                $token,
                new Key(config('auth.jwt_secret_key'), config('auth.jwt_algo'))
            );
        } catch (ExpiredException $e) {
            Log::debug('JWT Guard: Token expired.', ['token_substring' => substr($token, 0, 20) . '...', 'error' => $e->getMessage()]);
            return $this->user = null;
        } catch (SignatureInvalidException $e) {
            Log::warning('JWT Guard: Token signature invalid.', ['token_substring' => substr($token, 0, 20) . '...', 'error' => $e->getMessage()]);
            return $this->user = null;
        } catch (BeforeValidException $e) {
            Log::debug('JWT Guard: Token not yet valid (nbf claim).', ['token_substring' => substr($token, 0, 20) . '...', 'error' => $e->getMessage()]);
            return $this->user = null;
        } catch (UnexpectedValueException $e) {
            Log::warning('JWT Guard: Unexpected value during token decoding.', ['token_substring' => substr($token, 0, 20) . '...', 'error' => $e->getMessage()]);
            return $this->user = null;
        } catch (Exception $e) {
            Log::error('JWT Guard: Generic token decoding error.', ['token_substring' => substr($token, 0, 20) . '...', 'error' => $e->getMessage()]);
            return $this->user = null;
        }

        // 'sub' (subject) 
        if ($this->decodedToken && isset($this->decodedToken->sub)) {
            // UserProvider 
            $user = $this->provider->retrieveById($this->decodedToken->sub);
            if ($user) {
                // $this->user 
                return $this->user = $user;
            }
        }
        return $this->user = null;
    }

    /**
     * Get the token for the current request.
     *
     * @return string|null
     */
    public function getTokenForRequest(): ?string
    {
        $token = $this->request->bearerToken();

        if (empty($token)) {
            $token = $this->request->cookie('auth_token');
        }

        return $token;
    }

    /**
     * Validate a user's credentials.
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = []): bool
    {

        if (empty($credentials['id']) && (empty($credentials['email']) || empty($credentials['password']))) {
            return false;
        }
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user && $this->provider->validateCredentials($user, $credentials)) {
            return true;
        }
        return false;
    }

    /**
     * Get the decoded JWT payload.
     *
     * @return object|null
     */
    public function getDecodedToken(): ?object
    {
        return $this->decodedToken;
    }
}
