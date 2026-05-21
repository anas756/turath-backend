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
            // Get token from the URL query string
            $tokenFromUrl = $request->query('token');

            //  Find user 
            $user = User::where('email', $email)->first();
            if (!$user) {
                return redirect('http://localhost:3000/reset-token-confirmed?status=error&reason=' . urlencode('Utilisateur introuvable'));
            }

            //  Receive token array from MongoDB
            $authData = $user->auth_tokens;

            //  Security checks
            if (
                !$authData ||
                $authData['role'] !== 'reset password' ||
                $authData['token'] !== $tokenFromUrl ||
                ($authData['used'] ?? false)
            ) {
                return redirect('http://localhost:3000/reset-token-confirmed?status=error&reason=' . urlencode('Ce lien de réinitialisation est invalide ou a déjà été utilisé.'));
            }

            //  Verify Expiration
            if (now()->gt(\Carbon\Carbon::parse($authData['token_expires_at']))) {
                return redirect('http://localhost:3000/reset-token-confirmed?status=error&reason=' . urlencode('Ce lien a expiré (validité de 5min).'));
            }

            //  Set confirmed state so they are authorized to submit the new password next
            $authData['confirmed'] = true;
            $user->update([
                'auth_tokens' => $authData
            ]);

            //   Cleanly redirect with status, email, and token attached as query parameters
            $successUrl = 'http://localhost:3000/reset-token-confirmed' .
                '?status=success' .
                '&email=' . urlencode($email) .
                '&token=' . urlencode($tokenFromUrl);

            return redirect($successUrl);
        } catch (\Throwable $th) {
            Log::error("Verify Reset Token Error: " . $th->getMessage());
            return redirect('http://localhost:3000/reset-token-confirmed?status=error&reason=' . urlencode('Une erreur serveur est survenue.'));
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
