<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }
    public function update(User $userAuth, User $model)
    {
       
        return $model->CheckUserAuthOrAdminRole($userAuth);
    }
    public function destroy(User $userAuth, User $model)
    {
        return $model->CheckUserAuthOrAdminRole($userAuth);
    }
}
