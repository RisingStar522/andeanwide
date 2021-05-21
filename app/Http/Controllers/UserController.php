<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Auth\Events\Registered;

class UserController extends Controller
{
    public function register(Request $request, CreateNewUser $creator)
    {
        $user = $creator->create($request->all());
        $user->assignRole('user');
        // TODO: register country by ip address and balance currency id
        $user->balance_currency_id = 1;
        $user->save();
        event(new Registered($user));
        return $user;
    }

    public function registerAdminUser(Request $request, CreateNewUser $creator)
    {
        $user = $creator->create($request->all());
        $user->assignRole('base');
        return $user;
    }

    public function index()
    {
        $users = User::Role(['admin', 'compliance', 'base'])->paginate();
        return UserResource::collection($users);
    }

    public function all()
    {
        $users = User::role('user')->paginate();
        return UserResource::collection($users);
    }

    public function show(User $user)
    {
        return new UserResource($user);
    }

    public function addRole(Request $request, User $user)
    {
        $request->validate([
            'role'  => 'required|in:agent,admin,super_admin,compliance'
        ]);

        if(
            ($user->hasRole('user') && ($user->account_type === 'corporative' || $user->account_type === 'imports') && $request->role === 'agent') ||
            ($user->hasRole('base') && $request->role !== 'agent')
        ) {
            $user->assignRole($request->role);
            return new UserResource($user);
        }
        return response('Forbidden', Response::HTTP_FORBIDDEN);
    }

    public function removeRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:agent,admin,compliance'
        ]);
        $user->removeRole($request->role);
        return new UserResource($user);
    }

    /**
     * Cambia el monto del crédito máximo disponible para la cuenta
     */
    public function setCreditAmount(Request $request,User $user)
    {

        $user->balance_credit_limit = $request->amount;
        $user->save();
        return new UserResource($user);
    }

    /**
     * Cambia el monto del balance disponible a estrictamente lo que se mande
     * en el campo amount
     */
    public function changeBalanceAmount(Request $request,User $user)
    {

        $user->balance = $request->amount;
        $user->save();
        return new UserResource($user);
    }

    /**
     * Cambia el monto del balance disponible sumando (o restando en caso de mandar un numero negativo)
     * lo que se envie en el campo amount.
     *
     * Si se envia un número positivo en amount se le acredita al disponible
     * Si se envia un número negativo, se le debita.
     */
    public function addRemoveFromBalance(Request $request,User $user)
    {
        $user->balance = $user->balance + $request->amount;
        $user->save();
        return new UserResource($user);
    }

    public function setAccoutnType(Request $request)
    {
        $request->validate([
            'account_type' => 'nullable|in:personal,corporative,imports'
        ]);

        $user = Auth::user();
        $user->account_type = $request->input('account_type', 'personal');
        $user->save();

        return new UserResource($user);
    }

    public function setAccountTypeToCorporation(Request $request, User $user)
    {
        if ($user->acccount_type === 'personal') {
            return response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'account_type' => 'nullable|in:corporative,imports'
        ]);

        $user->account_type = $request->input('account_type', 'corporative');
        $user->save();

        return new UserResource($user);
    }

    public function setAgent(User $user)
    {
        if ($user->acccount_type === 'personal') {
            return response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $user->assignRole('agent');
        $user->save();

        return new UserResource($user);
    }

    public function removeAgent(User $user)
    {
        if ($user->acccount_type === 'personal') {
            return response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $user->removeRole('agent');
        $user->save();

        return new UserResource($user);
    }
}
