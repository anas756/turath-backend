<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;

class UserServices
{
    /**
     * Create a new user.
     */
    public function createUser(array $data): ?User
    {
        try {
            if (isset($data['password'])) {
                $data['password'] = $this->hashPass($data['password']);
            }

            $data['confirmed'] = false;
            $data['is_login'] = false;
            $data['last_login'] = null;

            return User::create($data);
        } catch (Exception $e) {
            Log::error("MongoDB Create Error: " . $e->getMessage());
            throw new Exception("Erreur lors de la création de l'utilisateur.");
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
            return $user->delete();
        } catch (Exception $e) {
            Log::error("MongoDB Delete Error: " . $e->getMessage());
            throw new Exception("Erreur lors de la suppression de l'utilisateur.");
        }
    }

    public function hashPass(string $pass): string
    {
        return Hash::make($pass);
    }
}
