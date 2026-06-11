<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\UserMailServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    protected $userMailServices ; 
    public function __construct(UserMailServices $userMailServices) {
        $this->userMailServices = $userMailServices;
    }
   
    public function login(LoginRequest $request)
    {
        try {
            //  Get validated data
            $credentials = $request->validated();
            //  Find user by email
            $user = User::where('email', $credentials['email'])->first();
            // Verify User and Password
            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials' 
                ], 401);
            }
            // check the acount confirmation
            if (!$user->confirmed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please confirm your email address first.',
                    'data' => [
                        'email' => $user->email 
                    ]
                ], 403);
            }
            //  Update login metadata
            $user->update([
                'is_login' => true,
                'last_login' => now(), 
            ]);
            $token = JWTAuth::fromUser($user);
            return response()->json([
                'success' => true,
                'message' => 'User logged in successfully',
                'data'    => $user,
                'token'   => $token
            ], 200);
        } catch (Exception $e) {
            Log::error("Login Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            //  Get the currently authenticated user from the Sanctum guard
            $user = auth('api')->user();
            // check user 
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }
            //  Update custom login status in MongoDB
            $user->update([
                'is_login' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User logged out successfully'
            ], 200);
        } catch (Exception $e) {
            Log::error("Logout Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during logout',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function manualSendEmailValidation(Request $request)  {
        try {
            // validate data
            $validated = $request->validate([
                'email' => ['required', 'email', 'exists:users,email']
            ]);
            // find user 
            $user = User::where('email', $validated['email'])->first();
            // check user 
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur introuvable'
                ], 404);  
            }
            // Check if already confirmed
            if ($user->confirmed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre email est déjà confirmé'
                ], 400);
            }
            // gererate token
            $token = Str::random(60);
            // update user info 
            $user->update([
                'auth_tokens' => null
            ]);
            $user->auth_tokens = [
                'token'            => $token,
                'role'             => 'confirm email',
                'used'             => false,
                'token_expires_at' => Carbon::now()->addHours(24)->toISOString(),
            ];
            $user->save();
            // service send confirm 
            $this->userMailServices->send_confirme_acount($token , $user);
            return response()->json([
                'success' => true,
                'message' => 'Email de confirmation envoyé avec succès'
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Email invalide ou introuvable',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Throwable $th) {
            Log::error("Manual Send Email Error: " . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email',
                'error'   => $th->getMessage()
            ], 500);
        }
    }

    public function sendResetPassToken(Request $request)  {
        try {
            // get email from query 
           $email = $request->input('email');
        //    find user 
           $user = User::where('email' ,$email)->first();
            //    check user exist 
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet e-mail n\'existe pas dans notre base de données.'
                ], 404);
            }
        // generate token 
        $token = Str::random(60);
        // remove old token 
          $user->update([
            'auth_tokens' =>null
        ]);
        // update user data
        $user->update([
            'auth_tokens' => [
                'token' => $token, 
                'role'  => 'reset password',
                'used'  => false,
                "confirmed" => false , 
                'token_expires_at' => now()->addMinutes(10)->toISOString() 
            ]
        ]);
      

    //  service send email verefication
    $this->userMailServices->send_reset_pass_token($token , $user);


            return response()->json([
                'success' => true,
                'message' => 'E-mail de réinitialisation envoyé avec succès.'
            ],200);
        } catch (\Throwable $th) {
            Log::error("Reset Password Error: " . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'envoi.',
                'error' => $th->getMessage()
            ], 500);
        }
    }
    public function getProfile()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found or unauthenticated.'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'user' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server Error validation failed.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
