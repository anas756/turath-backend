<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
   
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
}
