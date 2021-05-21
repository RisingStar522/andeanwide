<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticationController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if(!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response([
            'token' => Auth::user()->createToken("web")->plainTextToken
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        $user->currentAccessToken()->delete();
        $request->session()->forget(['XSRF-TOKEN', 'laravel_session']);

        return response([
            'message' => 'Successfully logout.'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $user->roles;
        if($user->identity) {
            $user->initials = Str::upper(Str::substr($user->identity->firstname, 0 , 1)) . Str::upper(Str::substr($user->identity->firstname, 0 , 1));
        } else {
            $user->initials = Str::upper(Str::substr($user->name, 0 , 1));
        }
        $user->disponibility = $user->disponibility;
        $user->company;
        $user->address;

        return response([
            'user'  => $user
        ]);
    }
}
