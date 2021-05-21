<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Identity;
use App\Models\User;
use App\Traits\HasSaveImage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class IdentityController extends Controller
{
    use HasSaveImage;

    public function store(Request $request)
    {
        $user = $request->user();

        if(!is_null($user->identity) && !$user->identity->isRejected) {
            return response('Forbidden', Response::HTTP_FORBIDDEN);
        } else if (isset($user->identity) && isset($user->identity->isRejected)) {
            $user->identity->delete();
        }

        $request->validate([
            'identity_number'           => 'required',
            'document_type'             => 'required',
            'firstname'                 => 'required',
            'lastname'                  => 'required',
            'dob'                       => 'required|date|before_or_equal:-18Years',
            'issuance_date'             => 'required|date|before_or_equal:Today',
            'expiration_date'           => 'required|date|after:Today',
            'gender'                    => 'required',
            'issuance_country_id'       => 'required|exists:countries,id',
            'nationality_country_id'    => 'required|exists:countries,id'
        ]);

        $identity = $request->only([
            'identity_number',
            'document_type',
            'firstname',
            'lastname',
            'dob',
            'issuance_date',
            'expiration_date',
            'gender',
            'profession',
            'activity',
            'position',
            'state',
            'issuance_country_id',
            'nationality_country_id',
        ]);

        $user->identity()->create($identity);

        return response(new UserResource($user), Response::HTTP_CREATED);
    }

    public function verifyIdentity(User $user)
    {
        if($user->hasAnyRole(['base', 'admin', 'super_admin', 'agent']) || is_null($user->identity) || $user->identity->isVerified || $user->identity->isRejected ) {
            return response('Forbidden', Response::HTTP_FORBIDDEN);
        }
        $user->identity->verified_at = now();
        $user->identity->save();
        return new UserResource($user);
    }

    public function rejectIdentity(Request $request, User $user)
    {
        if($user->hasAnyRole(['base', 'admin', 'super_admin', 'agent']) || is_null($user->identity) || $user->identity->isVerified || $user->identity->isRejected ) {
            return response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'rejection_reasons' => 'required'
        ]);

        $user->identity->rejected_at = now();
        $user->identity->rejection_reasons = $request->input('rejection_reasons');
        $user->identity->save();
        return new UserResource($user);
    }

    public function attachFront(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:3072'
        ]);

        $user = $request->user();
        $user->identity->front_image_url = $this->saveImage($request->file('image'), $user, 'identity', 'front_');
        $user->identity->save();

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function attachBack(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:3072'
        ]);
        $user = $request->user();
        $user->identity->back_image_url = $this->saveImage($request->file('image'), $user, 'identity', 'back_');
        $user->identity->save();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
