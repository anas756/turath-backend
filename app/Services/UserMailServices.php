<?php

namespace App\Services;

use App\Mail\ConfirmAccount;
use App\Mail\ConfirmAcount;
use App\Mail\sendResetPassTokenMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class UserMailServices {

    public function send_confirme_acount($confirmationToken, User $user)
    {
        Mail::to($user->email)->send(new ConfirmAccount($confirmationToken, $user));
    }
    public function send_reset_pass_token($confirmationToken, User $user)
    {
        Mail::to($user->email)->send(new sendResetPassTokenMail($confirmationToken, $user));
    }
}