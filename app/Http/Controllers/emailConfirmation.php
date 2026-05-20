<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class emailConfirmation extends Controller
{
    protected $userServices;
    public function __construct(UserServices $userServices)
    {
        $this->userServices = $userServices;
    }

    public function confirmingEmail(Request $request, $email)
    {
        try {
            // get token 
            $token = $request->query('token');
            // find user 
            $user = User::where('email', $email)->first();
            if (!$user) {
                return redirect('http://localhost:3000/email-confirmed?status=error&reason=not_found');
            }
            $authData = $user->auth_tokens;

            // check token exist and used
            if (!$authData || $authData['token'] !== $token || ($authData['used'] ?? false)) {
                return redirect('http://localhost:3000/email-confirmed?status=error&reason=invalid_token');
            }

            //  Check Expiration
            if (now()->gt(\Carbon\Carbon::parse($authData['token_expires_at']))) {
                return redirect('http://localhost:3000/email-confirmed?status=error&reason=expired');
            }
            // sevice confirm email 
            $this->userServices->confirmEmail($user);
            return redirect('http://localhost:3000/email-confirmed?status=success');
        } catch (\Throwable $th) {
            Log::error("Confirm Email Error: " . $th->getMessage());
            return redirect('http://localhost:3000/email-confirmed?status=error&reason=server_error');
        }
    }
    public function verifyResetToken(Request $request, $email)
    {
        try {
            // get token 
            $tokenFromUrl = $request->query('token');

            // find user 
            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Utilisateur introuvable'], 404);
            }

            // recive token from db
            $authData = $user->auth_tokens;

            // securety 
            if (
                !$authData ||
                $authData['role'] !== 'reset password' ||
                $authData['token'] !== $tokenFromUrl ||
                ($authData['used'] ?? false)
            ) {

                return response()->json([
                    'success' => false,
                    'message' => 'Ce lien de réinitialisation est invalide ou a déjà été utilisé.'
                ], 403);
            }

            // verefay expiration
            if (now()->gt(\Carbon\Carbon::parse($authData['token_expires_at']))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce lien a expiré (validité de 1h).'
                ], 403);
            }
            $authData['confirmed'] = true;
            // update user info 
            $user->update([
                'auth_tokens' => $authData
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Token valide. Vous pouvez modifier votre mot de passe.',
                'data' => [
                    'email' => $email,
                    'token' => $tokenFromUrl
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'error' => $th->getMessage()], 500);
        }
    }
    public function checkVerified($email)
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['verified' => false], 404);
        }
        return response()->json(['verified' => (bool) $user->confirmed], 200);
    }
}
