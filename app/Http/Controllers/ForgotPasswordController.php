<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\PasswordBroker;

class ForgotPasswordController extends Controller
{
    
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $response = $this->broker()->sendResetLink(
            $request->only('email')
        );

        return $response == Password::RESET_LINK_SENT
                    ? response()->json(['message' => 'Reset link sent to your email address.'], 200)
                    : response()->json(['error' => 'Unable to send reset link'], 400);
    }


    public function broker()
    {
        return Password::broker();
    
    }


    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
            'token' => 'required|string',
        ]);

        $response = $this->broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = bcrypt($password);
                $user->save();
            }
        );

        return $response == Password::PASSWORD_RESET
                    ? response()->json(['message' => 'Password reset successfully'], 200)
                    : response()->json(['error' => 'Unable to reset password'], 400);
    }
    
}
