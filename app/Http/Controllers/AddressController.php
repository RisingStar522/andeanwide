<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\HasSaveImage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    use HasSaveImage;

    public function store(Request $request)
    {
        $user = Auth::user();

        if(!is_null($user->address) && !$user->address->isRejected) {
            return response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'country_id'    => 'required|exists:countries,id',
            'address'       => 'required',
            'state'         => 'required',
            'cod'           => 'required',
        ]);

        $address = $request->only([
            'user_id',
            'country_id',
            'address',
            'address_ext',
            'state',
            'city',
            'cod',
        ]);

        $user->address()->create($address);

        // return response($user, Response::HTTP_CREATED);
        return response(new UserResource($user), Response::HTTP_CREATED);
    }

    public function verifyAddress(User $user)
    {
        if( $user->hasAnyRole(['base', 'admin', 'super_admin', 'agent']) || is_null($user->address) || $user->address->isVerified || $user->address->isRejected ){
            return response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $user->address->verified_at = now();
        $user->address->save();
        return new UserResource($user);
    }

    /** @test */
    public function rejectAddress(User $user)
    {
        if( $user->hasAnyRole(['base', 'admin', 'super_admin', 'agent']) || is_null($user->address) || $user->address->isVerified || $user->address->isRejected ){
            return response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $user->address->rejected_at = now();
        $user->address->save();
        return new UserResource($user);
    }

    public function attachImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:3072'
        ]);
        $user = $request->user();
        $user->address->image = $this->saveImage($request->file('image'), $user, 'address', 'addr_');
        $user->address->save();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
