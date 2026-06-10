<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class emailConfirmation extends Controller
{
    protected $userServices;
    protected string $frontendUrl;

    public function __construct(UserServices $userServices)
    {
        $this->userServices = $userServices;
        $this->frontendUrl  = config('app.frontend_url', 'http://localhost:3000');
    }

    public function confirmingEmail(Request $request, $email)
    {
        try {
            $token = $request->query('token');
            $user  = User::where('email', $email)->first();

            if (!$user) {
                return redirect($this->frontendUrl . '/email-confirmed?status=error&reason=not_found');
            }
            $authData = $user->auth_tokens;

            if (!$authData || $authData['token'] !== $token || ($authData['used'] ?? false)) {
                return redirect($this->frontendUrl . '/email-confirmed?status=error&reason=invalid_token');
            }

            if (now()->gt(\Carbon\Carbon::parse($authData['token_expires_at']))) {
                return redirect($this->frontendUrl . '/email-confirmed?status=error&reason=expired');
            }

            $this->userServices->confirmEmail($user);

            return redirect($this->frontendUrl . '/email-confirmed?status=success');
        } catch (\Throwable $th) {
            Log::error("Confirm Email Error: " . $th->getMessage());
            return redirect($this->frontendUrl . '/email-confirmed?status=error&reason=server_error');
        }
    }

    public function verifyResetToken(Request $request, $email)
    {
        try {
            $tokenFromUrl = $request->query('token');
            $user         = User::where('email', $email)->first();

            if (!$user) {
                return redirect($this->frontendUrl . '/reset-token-confirmed?status=error&reason=' . urlencode('Utilisateur introuvable'));
            }

            $authData = $user->auth_tokens;

            if (
                !$authData ||
                $authData['role'] !== 'reset password' ||
                $authData['token'] !== $tokenFromUrl ||
                ($authData['used'] ?? false)
            ) {
                return redirect($this->frontendUrl . '/reset-token-confirmed?status=error&reason=' . urlencode('Ce lien de réinitialisation est invalide ou a déjà été utilisé.'));
            }

            if (now()->gt(\Carbon\Carbon::parse($authData['token_expires_at']))) {
                return redirect($this->frontendUrl . '/reset-token-confirmed?status=error&reason=' . urlencode('Ce lien a expiré (validité de 5min).'));
            }

            $authData['confirmed'] = true;
            $user->update(['auth_tokens' => $authData]);

            $successUrl = $this->frontendUrl . '/reset-token-confirmed' .
                '?status=success' .
                '&email=' . urlencode($email) .
                '&token=' . urlencode($tokenFromUrl);

            return redirect($successUrl);
        } catch (\Throwable $th) {
            Log::error("Verify Reset Token Error: " . $th->getMessage());
            return redirect($this->frontendUrl . '/reset-token-confirmed?status=error&reason=' . urlencode('Une erreur serveur est survenue.'));
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
