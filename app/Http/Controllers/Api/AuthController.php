<?php

namespace App\Http\Controllers\Api; // Correct namespace

// Import necessary classes

use App\Helper\JwtHelper;
use App\Models\User;
use Illuminate\Http\Request; // Standard Request, though FormRequests are preferred for validation
use Illuminate\Support\Facades\Auth; // Laravel's Auth facade
use Illuminate\Support\Facades\Hash; // For hashing passwords
use Illuminate\Support\Facades\Log; // For logging errors and info
use Illuminate\Support\Facades\DB; // For database transactions and direct table access (e.g., password_reset_tokens)
use Illuminate\Support\Facades\Mail; // For sending emails
use Illuminate\Support\Str; // For generating random strings (e.g., tokens)
use Exception; // For catching general exceptions

// Import our custom classes/traits
use App\Http\Controllers\Api\BaseApiController; // Our base API controller
use App\Services\FileStorageService; // Our file storage service
use App\Mail\Auth\PasswordResetOtpMail; // Our mailable for OTP

// Import Form Request classes for validation
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\User\UpdateUserProfileRequest;
use App\Http\Resources\UserResource; // For formatting user data in responses
use App\Notifications\Auth\NewUserRegisteredNotification;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Illuminate\Support\Carbon;
// Spatie Permission model (used when assigning roles)
use Spatie\Permission\Models\Role;


class AuthController extends BaseApiController // Extend our BaseApiController
{
    use JwtHelper; // Use our JWT helper trait for token generation

    /**
     * Constructor to apply middleware.
     * 'auth:api' middleware will protect routes, except for specified ones.
     * Our custom 'firebase_jwt' guard (aliased as 'api') will be used.
     */
    public function __construct()
    {
        $this->middleware('auth:api')->except([
            'register',
            'login',
            'forgotPassword',
            'verifyOtp',
            'resetPassword',
            'refreshToken' // refreshToken endpoint itself might need a valid refresh token, not an access token
        ]);
    }

    /**
     * Register a new user.
     * Uses RegisterUserRequest for validation.
     */
    public function register(RegisterUserRequest $request)
    {
        // It's good practice to wrap database operations in a transaction
        // especially if multiple records are created/updated (e.g., user, role assignment, activity log).
        DB::beginTransaction();

        try {
            // Get validated data from the FormRequest
            $validatedData = $request->validated();

            // Create the user
            $newUser = User::create([ // Renamed to $newUser for clarity
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => $validatedData['password'], // Password will be automatically hashed
                'is_active' => true,
            ]);

            // --- Assign a default role (Optional, but common) ---
            // Example: assign 'customer' role to all self-registered users.
            // Make sure 'customer' role with 'api' guard exists (created by seeder).

            $defaultRoleName = 'customer'; // Or 'user', 'member', etc.
            $defaultRole = Role::where('name', $defaultRoleName)->where('guard_name', 'api')->first();
            if ($defaultRole) {
                $newUser->assignRole($defaultRole);
            } else {
                Log::warning("Default role '{$defaultRoleName}' for API guard not found during registration for user: {$newUser->email}.");
                // Decide if this should be a critical error or just a warning.
            }



            // Generate JWT tokens for the newly registered user
            $tokens = $this->generateTokensForUser($newUser);

            // Log activity: The user who registered is the causer and also the subject.
            activity()->causedBy($newUser)->performedOn($newUser)
                ->log('User registered: ' . $newUser->email);


            // --- Send Notification to Super Admins ---
            try {
                // Find all users with the 'super-admin' role for the 'api' guard
                $superAdmins = User::whereHas('roles', function ($query) {
                    $query->where('name', 'super-admin')->where('guard_name', 'api');
                })->get();

                if ($superAdmins->isNotEmpty()) {
                    // Send notification to each super admin
                    // Using notifyNow for immediate dispatch (doesn't require queue worker)
                    // Using notify() would queue it if your queue driver is set up.
                    foreach ($superAdmins as $superAdmin) {
                        $superAdmin->notifyNow(new NewUserRegisteredNotification($newUser));
                    }
                    Log::info("New user registration notification successfully dispatched to super admins for new user: {$newUser->email}");
                } else {
                    Log::info("No super admins found to notify for new user registration: {$newUser->email}. This might be expected if no super admins are configured or if they don't have notification preferences set up for this.");
                }
            } catch (Exception $notificationException) {
                // Log the notification error but do not let it fail the main registration process.
                // The user registration should still succeed even if notifications fail.
                Log::error('Failed to send new user registration notification to super admins.', [
                    'new_user_id' => $newUser->id,
                    'new_user_email' => $newUser->email,
                    'notification_error_message' => $notificationException->getMessage(),
                    'notification_error_trace_snippet' => substr($notificationException->getTraceAsString(), 0, 500),
                ]);
            }

            DB::commit(); // Commit the transaction if all operations were successful

            return $this->successResponse(
                array_merge($tokens, ['user' => new UserResource($newUser->loadMissing('roles'))]), // Eager load roles for the resource
                'User registered successfully. A notification has been sent to administrators.', // Updated message
                201 // HTTP 201 Created status
            );
        } catch (Exception $e) {
            DB::rollBack(); // Rollback the transaction if any error occurred during the try block
            Log::error('User registration failed catastrophically.', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(), // Log full trace for debugging
                'request_data' => $request->except('password', 'password_confirmation') // Log request data without sensitive info
            ]);
            // Return a generic error to the client for security
            return $this->errorResponse('User registration failed. Please try again later or contact support.', 500);
        }
    }

    /**
     * Log in an existing user.
     * Uses LoginRequest for validation.
     */
    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->validated(); // Get email and password

            // Attempt to find the user by email
            $user = User::where('email', $credentials['email'])->first();

            // Check if user exists and password is correct
            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return $this->unauthorizedResponse('Invalid email or password.');
            }

            // Generate JWT tokens
            $tokens = $this->generateTokensForUser($user);

            // Log activity
            activity()->causedBy($user)->log('User logged in');

            return $this->successResponse(
                array_merge($tokens, ['user' => new UserResource($user)]),
                'Login successful.'
            );
        } catch (Exception $e) {
            Log::error('Login attempt failed.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email' => $request->email
            ]);
            return $this->errorResponse('Login failed. Please try again later.', 500);
        }
    }

    /**
     * Get the authenticated user's profile.
     * Protected by 'auth:api' middleware.
     */
    public function me(Request $request)
    {
        try {
            // The 'auth:api' middleware ensures $request->user() is the authenticated user.
            // Our FirebaseJwtGuard handles retrieving the user based on the token.
            $user = $request->user();

            if (!$user) {
                // This should ideally not happen if middleware is correctly applied and token is valid.
                return $this->unauthorizedResponse('User not authenticated.');
            }

            // Load relationships if needed for UserResource, e.g., store, roles
            // $user->loadMissing(['store', 'roles']); // 'roles' comes from Spatie

            return $this->successResponse(
                new UserResource($user->loadMissing('roles')), // Eager load roles if not already loaded
                'User profile fetched successfully.'
            );
        } catch (Exception $e) {
            Log::error('Failed to fetch authenticated user profile.', [
                'user_id' => auth()->id(), // Get ID of authenticated user if available
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Could not fetch user profile.', 500);
        }
    }

    /**
     * Refresh the access token using a valid refresh token.
     */
    public function refreshToken(Request $request)
    {
        $clientRefreshToken = $request->input('refresh_token');

        if (!$clientRefreshToken) {
            return $this->errorResponse('Refresh token not provided.', 400);
        }

        try {
            // Decode the refresh token using our JwtHelper trait
            $decodedRefresh = $this->decodeJwtToken($clientRefreshToken);

            // Validate the decoded refresh token payload
            if (
                !$decodedRefresh ||
                !isset($decodedRefresh->sub) || // User ID
                !isset($decodedRefresh->type) || $decodedRefresh->type !== 'refresh' || // Token type
                !isset($decodedRefresh->jti) // JWT ID (for checking against stored refresh tokens)
            ) {
                throw new Exception('Invalid refresh token structure or type.');
            }

            $user = User::find($decodedRefresh->sub);
            if (!$user) {
                throw new Exception('User associated with the refresh token not found.');
            }

            // Check if the refresh token (JTI) is valid, not revoked, and not expired in the database
            $storedRefreshToken = $user->refreshTokens()
                ->where('token', $decodedRefresh->jti) // Match the unique ID
                ->where('revoked', false)
                ->where('expires_at', '>', now())
                ->first();

            if (!$storedRefreshToken) {
                throw new Exception('Refresh token is invalid, revoked, or has expired in database.');
            }

            // Optional: Implement refresh token rotation (invalidate old, issue new refresh token)
            // For now, we'll just issue a new access token.
            // $storedRefreshToken->update(['revoked' => true]); // Invalidate the used refresh token

            // Generate a new access token (only)
            $newAccessTokenPayload = [
                'iss' => url('/'),
                'aud' => url('/'),
                'iat' => now()->timestamp,
                'nbf' => now()->timestamp,
                'exp' => now()->timestamp + config('auth.jwt_access_token_ttl'),
                'sub' => $user->id,
                'type' => 'access',
                'uid' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'sid' => $user->store_id,
                'roles' => $user->getRoleNames()->toArray(),
            ];
            $newAccessToken = JWT::encode($newAccessTokenPayload, config('auth.jwt_secret_key'), config('auth.jwt_algo'));

            return $this->successResponse([
                'access_token' => $newAccessToken,
                'token_type' => 'bearer',
                'access_expires_in' => config('auth.jwt_access_token_ttl'),
                // Optionally, you can return a new refresh token here as well if implementing rotation
                // 'refresh_token' => $newRefreshToken,
                // 'refresh_expires_in' => config('auth.jwt_refresh_token_ttl'),
            ], 'Access token refreshed successfully.');
        } catch (ExpiredException $e) { // This would be for JWT::decode if it throws ExpiredException
            Log::info('Refresh token JWT itself has expired during decoding.', ['error' => $e->getMessage()]);
            return $this->unauthorizedResponse('Refresh token has expired. Please log in again.');
        } catch (Exception $e) {
            Log::warning('Refresh token validation or new access token generation failed.', [
                'error' => $e->getMessage(),
                'refresh_token_provided' => !empty($clientRefreshToken),
            ]);
            // Provide a more generic error message to the client for security
            return $this->unauthorizedResponse('Invalid or expired refresh token. Please log in again.');
        }
    }


    /**
     * Log out the authenticated user.
     * For JWT, this primarily means the client should discard the tokens.
     * Optionally, revoke the refresh token on the server side if it's tracked.
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user(); // Get the authenticated user

            // Attempt to revoke the refresh token if provided and tracked
            // Client should send its current refresh_token (the JWT string) in the request body
            $clientRefreshTokenJwt = $request->input('refresh_token_to_revoke');
            if ($user && $clientRefreshTokenJwt) {
                $decoded = $this->decodeJwtToken($clientRefreshTokenJwt);
                if ($decoded && isset($decoded->jti) && isset($decoded->type) && $decoded->type === 'refresh') {
                    // Use the JTI (unique ID) from the refresh token to revoke it
                    $this->revokeRefreshTokenByJti($user, $decoded->jti);
                    activity()->causedBy($user)->log('User refresh token revoked during logout');
                }
            }

            // For stateless JWT, there's no server-side session to invalidate for the access token.
            // The client is responsible for destroying the access token.

            if ($user) {
                activity()->causedBy($user)->log('User logged out');
            }

            return $this->successResponse(null, 'Successfully logged out. Please discard your tokens.');
        } catch (Exception $e) {
            Log::error('Logout process failed.', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Logout failed. Please try again later.', 500);
        }
    }

    /**
     * Handle request to send a password reset OTP.
     * Uses ForgotPasswordRequest for validation.
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            $user = User::where('email', $request->email)->first();

            // Even if user not found, return a success-like response to prevent email enumeration
            if (!$user) {
                Log::info('Password reset requested for non-existent email.', ['email' => $request->email]);
                // It's good practice not to reveal if an email exists or not
                return $this->successResponse(null, 'If your email address is registered, you will receive a password reset OTP shortly.');
            }

            $otp = (string)random_int(100000, 999999); // Generate a 6-digit OTP
            $expiresAt = now()->addMinutes(config('auth.passwords.users.expire', 15)); // OTP expiry time (e.g., 15 minutes)

            // Store/update the OTP in the password_reset_tokens table
            // Laravel's default password_reset_tokens table uses 'email' and 'token' (hashed).
            // We'll store the hashed OTP.
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($otp), // Store a hash of the OTP
                    'created_at' => now()
                    // The default table might not have an 'expires_at' column.
                    // Token validity is usually checked based on 'created_at' + expiry time.
                ]
            );

            // Send the OTP email
            Mail::to($user->email)->send(new PasswordResetOtpMail($otp, $user->name));

            activity()->causedBy($user)->log('Password reset OTP requested and sent');
            return $this->successResponse(null, 'A password reset OTP has been sent to your email address.');
        } catch (Exception $e) {
            Log::error('Forgot password OTP sending failed.', [
                'email' => $request->email,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Could not process password reset request. Please try again later.', 500);
        }
    }

    /**
     * Verify the provided OTP for password reset.
     * Uses VerifyOtpRequest for validation.
     */
    public function verifyOtp(VerifyOtpRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $email = $validatedData['email'];
            $otpProvided = $validatedData['otp'];

            // Retrieve the password reset record from the database
            $resetRecord = DB::table('password_reset_tokens')->where('email', $email)->first();

            // Check if a record exists and if the OTP matches (hashed OTP)
            if (!$resetRecord || !Hash::check($otpProvided, $resetRecord->token)) {
                return $this->errorResponse('Invalid or expired OTP.', 422);
            }

            // Check if the OTP has expired (e.g., 15 minutes from creation)
            $otpExpiryMinutes = config('auth.passwords.users.expire', 15);
            if (now()->diffInMinutes($resetRecord->created_at) > $otpExpiryMinutes) {
                // Optionally delete the expired OTP record
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                return $this->errorResponse('OTP has expired. Please request a new one.', 422);
            }

            // OTP is valid. Generate a temporary "reset flow token" for the next step (actual password reset).
            // This flow token proves that OTP verification was successful.
            $resetFlowToken = Str::random(60); // A secure random string

            // Update the record with this flow token, replacing the OTP hash.
            // This flow token will be used in the resetPassword step.
            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->update([
                    'token' => Hash::make($resetFlowToken), // Store a hash of the flow token for security
                    'created_at' => now() // Update timestamp for flow token validity
                ]);

            // Log activity (user might not be authenticated here, so log by email)
            $user = User::where('email', $email)->first();
            if ($user) activity()->performedOn($user)->log('Password reset OTP verified');

            return $this->successResponse(
                ['reset_flow_token' => $resetFlowToken], // Send the unhashed flow token to client
                'OTP verified successfully. You can now reset your password using the provided flow token.'
            );
        } catch (Exception $e) {
            Log::error('OTP verification failed.', [
                'email' => $request->email,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Could not verify OTP. Please try again later.', 500);
        }
    }

    /**
     * Reset the user's password using the reset flow token.
     * Uses ResetPasswordRequest for validation.
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $email = $validatedData['email'];
            $flowTokenProvided = $validatedData['reset_flow_token'];
            $newPassword = $validatedData['password'];

            // Retrieve the password reset record
            $resetRecord = DB::table('password_reset_tokens')->where('email', $email)->first();

            // Check if record exists and if the flow token matches (hashed flow token)
            if (!$resetRecord || !Hash::check($flowTokenProvided, $resetRecord->token)) {
                return $this->errorResponse('Invalid or expired password reset token.', 422);
            }

            // Check flow token expiry (e.g., 10-15 minutes from OTP verification)
            $flowTokenExpiryMinutes = 15; // Or make this configurable
            if (now()->diffInMinutes($resetRecord->created_at) > $flowTokenExpiryMinutes) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                return $this->errorResponse('Password reset token has expired. Please start the process again.', 422);
            }

            // Find the user and update their password
            $user = User::where('email', $email)->first();
            if (!$user) {
                // Should not happen if email validation (exists:users) worked in FormRequest
                return $this->errorResponse('User not found.', 404);
            }

            $user->password = $newPassword; // Password will be hashed by User model's $casts
            $user->save();

            // Delete the password reset record from the database as it's now used
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            // Log activity
            activity()->causedBy($user)->performedOn($user)->log('Password reset successfully');

            return $this->successResponse(null, 'Password has been reset successfully. You can now log in with your new password.');
        } catch (Exception $e) {
            Log::error('Password reset failed.', [
                'email' => $request->email,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Could not reset password. Please try again later.', 500);
        }
    }

    // AuthController@resetPassword
// public function resetPassword(ResetPasswordRequest $request)
// {
//     Log::info('--- Reset Password Attempt ---');
//     $validatedData = $request->validated(); // Ensure this runs first
//     Log::info('ResetPassword Request Validated Data: ', $validatedData);

//     $email = $validatedData['email'];
//     $flowTokenProvidedByClient = $validatedData['reset_flow_token']; // Raw token from client
//     $newPassword = $validatedData['password'];

//     try {
//         $resetRecord = DB::table('password_reset_tokens')->where('email', $email)->first();

//         if (!$resetRecord) {
//             Log::warning("ResetPassword: No record found in password_reset_tokens for email: {$email}");
//             return $this->errorResponse('Invalid or expired password reset token (record not found).', 422);
//         }
//         Log::info('ResetPassword: Record found in DB:', (array) $resetRecord);

//         // This is the most crucial check
//         $isTokenMatch = Hash::check($flowTokenProvidedByClient, $resetRecord->token);
//         Log::info("ResetPassword: Hash::check result for flow token '{$flowTokenProvidedByClient}' against DB token '{$resetRecord->token}': " . ($isTokenMatch ? 'MATCH' : 'NO MATCH'));

//         if (!$isTokenMatch) {
//             return $this->errorResponse('Invalid or expired password reset token (token mismatch).', 422);
//         }

//         $flowTokenExpiryMinutes = 15; // Or config('auth.passwords.users.expire_token', 15)
//         $minutesSinceTokenCreation = now()->diffInMinutes(Carbon::parse($resetRecord->created_at)); // Ensure created_at is Carbon instance
//         Log::info("ResetPassword: Minutes since token creation: {$minutesSinceTokenCreation} (Expiry set to {$flowTokenExpiryMinutes} mins)");

//         if ($minutesSinceTokenCreation > $flowTokenExpiryMinutes) {
//             Log::warning("ResetPassword: Flow token expired for email: {$email}");
//             DB::table('password_reset_tokens')->where('email', $email)->delete(); // Delete expired token
//             return $this->errorResponse('Password reset token has expired. Please start the process again.', 422);
//         }

//         $user = User::where('email', $email)->first();
//         // ... (rest of the method: update password, delete token, success response) ...
//         if (!$user) {
//             Log::error("ResetPassword: User not found with email: {$email}, though reset token existed.");
//             return $this->errorResponse('User not found for password reset.', 404); // Should be rare
//         }

//         $user->password = $newPassword; // Hashed by model accessor
//         $user->save();
//         Log::info("ResetPassword: Password updated successfully for user: {$email}");

//         DB::table('password_reset_tokens')->where('email', $email)->delete();
//         Log::info("ResetPassword: Reset token deleted for email: {$email}");

//         activity()->causedBy($user)->performedOn($user)->log('Password reset successfully');
//         return $this->successResponse(null, 'Password has been reset successfully. You can now log in with your new password.');

//     } catch (Exception $e) {
//         // ... (general error logging) ...
//     }
// }
    /**
     * Update the authenticated user's profile information.
     * Uses UpdateUserProfileRequest for validation.
     * Uses FileStorageService for handling profile photo upload.
     */
    public function updateProfile(UpdateUserProfileRequest $request, FileStorageService $fileService)
    {
        try {
            $user = $request->user(); // Get the authenticated user
            $validatedData = $request->validated(); // Get validated data

            // Handle profile photo upload
            if ($request->hasFile('profile_photo')) {
                // Delete old photo if it exists
                if ($user->profile_photo_path) {
                    $fileService->delete($user->profile_photo_path);
                }
                // Store the new photo
                $newPhotoPath = $fileService->store($request->file('profile_photo'), 'user-profiles');
                if ($newPhotoPath) {
                    $validatedData['profile_photo_path'] = $newPhotoPath;
                } else {
                    Log::warning("Could not store profile photo for user: {$user->id}. Update proceeding without photo change.");
                    // Optionally, you could return an error if photo upload is critical and failed
                }
            }

            // Remove 'profile_photo' from validatedData if it exists, as it's a file object, not a db column
            // The path is already in $validatedData['profile_photo_path'] if uploaded.
            if (isset($validatedData['profile_photo'])) {
                unset($validatedData['profile_photo']);
            }


            // Update user details
            // $user->fill($validatedData) and $user->save() is also an option
            if (isset($validatedData['name'])) {
                $user->name = $validatedData['name'];
            }
            if (isset($validatedData['email'])) {
                // If email is being changed, you might want to re-verify it.
                // For simplicity, we are just updating it here.
                $user->email = $validatedData['email'];
            }
            if (isset($validatedData['profile_photo_path'])) {
                $user->profile_photo_path = $validatedData['profile_photo_path'];
            }
            // Add other fields as needed

            $user->save();

            // Log activity
            activity()->causedBy($user)->performedOn($user)->log('User profile updated');

            return $this->successResponse(
                new UserResource($user->fresh()), // Return fresh user data with UserResource
                'Profile updated successfully.'
            );
        } catch (Exception $e) {
            Log::error('Profile update failed for user.', [
                'user_id' => $request->user()->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Could not update profile. Please try again later.', 500);
        }
    }
}
