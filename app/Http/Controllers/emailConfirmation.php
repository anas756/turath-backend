<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class emailConfirmation extends Controller
{
    protected $userServices ; 
    public function __construct(UserServices $userServices) {
        $this->userServices = $userServices;
    }

    public function confirmingEmail($token)
    {
        try {
            $user = User::where('auth_tokens.token', $token)->first(); 

            if (!$user  ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalide ou utilisateur introuvable'
                ], 404);
            }

            $this->userServices->confirmEmail($user);

            return response()->json([
                'success' => true,
                'message' => 'Email confirmé avec succès'
            ], 200);
        } catch (\Throwable $th) {
            Log::error("Confirm Email Error: " . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la confirmation',
                'error'   => $th->getMessage()
            ], 500);
        }
    }
}
