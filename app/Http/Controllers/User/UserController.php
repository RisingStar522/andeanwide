<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Actions\Fortify\CreateNewUser;

class UserController extends Controller
{
    public function register(Request $request, CreateNewUser $creator)
    {
        $user = $creator->create($request->all());
        $user->assignRole('user');
        return $user;
    }
}
