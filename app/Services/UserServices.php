<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;

class UserServices
{
    /**
     * Create a new user.
     */
    public function createUser(array $data , $token): ?User
    {
        try {
            $token_expired_at = null ;
            if (isset($data['password'])) {
                $data['password'] = $this->hashPass($data['password']);
            }
            if($token) {
                $token_expired_at = Carbon::now()->addHours(24);
               
            }
            $data['confirmed'] = false;
            $data['is_login'] = false;
            $data['last_login'] = null;
            
            return User::updateOrCreate($data);
        } catch (Exception $e) {
            Log::error("MongoDB Create Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing user.
     * * @param array $data New data
     * @param User $user The User model instance
     * @return User
     */
    public function updateUser(array $data, User $user): User
    {
        try {
            if (!empty($data['password'])) {
                $data['password'] = $this->hashPass($data['password']);
            } else {
                unset($data['password']);
            }
            $user->update($data);
            return $user->fresh();
        } catch (Exception $e) {
            Log::error("MongoDB Update Error: " . $e->getMessage());
            throw new Exception("Erreur lors de la modification de l'utilisateur.");
        }
    }
    public function deleteUser(User $user )  {
        try {
            return $user->forceDelete();
        } catch (Exception $e) {
            Log::error("MongoDB Delete Error: " . $e->getMessage());
            throw new Exception("Erreur lors de la suppression de l'utilisateur.");
        }
    }

    // confirm email 
    public function confirmEmail(User $user)
    {
        try {
            // Set confirmed
            $user->confirmed = true;
            //  Remove token from embedded array
            $user->auth_tokens = null;
            //  Save changes to MongoDB
            $user->save();
            return $user;
        } catch (\Throwable $th) {
            Log::error("Confirm Email Error: " . $th->getMessage());
            throw $th;
        }
    }

    public function hashPass(string $pass): string
    {
        return Hash::make($pass);
    }
}
