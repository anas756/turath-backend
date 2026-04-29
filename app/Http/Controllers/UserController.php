<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Services\UserMailServices;
use App\Services\UserServices;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use AuthorizesRequests;
    protected $userServices, $userMailServices;

    public function __construct(UserServices $userServices, UserMailServices $userMailServices)
    {
        $this->userServices = $userServices;
        $this->userMailServices = $userMailServices;
    }

    /**
     * Afficher la liste des utilisateurs.
     */
    public function index()
    {
        try {
            $allUsers = User::all();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateurs récupérés avec succès',
                'data'    => $allUsers
            ], 200);
        } catch (Exception $e) {
            Log::error("Erreur Index : " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Échec de la récupération des utilisateurs',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouvel utilisateur.
     */
    public function store(StoreUserRequest $request)
    {
        try {
            $validated = $request->validated();
           

            $token = Str::random(60); 
            $validated['auth_tokens'] = $token ? [
                'token'            => $token,
                'role'             => 'confim acount',
                'used'             => false,
                'token_expires_at' => Carbon::now()->addHours(24)->toISOString(),
            ] : null;
            $userCreated = $this->userServices->createUser($validated);
            $this->userMailServices->send_confirme_acount($token, $userCreated);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès. Veuillez confirmer votre adresse e-mail.',
                'data'    => $userCreated
            ], 201);
        } catch (Exception $e) {
            Log::error("Erreur Store : " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Échec de la création de l\'utilisateur',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un utilisateur spécifique.
     */
    public function show(User $user)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $user
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable',
            ], 404);
        }
    }

    /**
     * Mettre à jour un utilisateur.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            // Vérifier la policy
            $this->authorize('update', $user);

            // Données validées
            $validated = $request->validated();

            $updatedUser = $this->userServices->updateUser($validated, $user);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès',
                'data' => $updatedUser
            ], 200);
        } catch (Exception $e) {
            Log::error("Erreur Update : " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un utilisateur.
     */
    public function destroy(User $user)
    {
        try {
            $this->authorize('destroy', $user);

            $this->userServices->deleteUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès'
            ], 200);
        } catch (Exception $e) {
            Log::error("Erreur Suppression : " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    
    public function updatePassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed', 
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->auth_tokens['token'] !== $request->token) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        
        $user->update([
            'password' => Hash::make($request->password),
            'auth_tokens' => null
        ]);

        return response()->json(['success' => true, 'message' => 'Mot de passe mis à jour !']);
    }


    
}
