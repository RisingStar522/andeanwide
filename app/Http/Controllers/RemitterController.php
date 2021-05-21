<?php

namespace App\Http\Controllers;

use App\Models\Remitter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\RemitterResource;

class RemitterController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();
        if($request->query() && !$request->query('page')) {
            $remitters = Remitter::where('user_id', $user->id);
            foreach ($request->query() as $key => $value) {
                $remitters->where($key, 'like', '%' . $value . '%');
            }
            return RemitterResource::collection($remitters->get());
        }
        $remitters = Remitter::where('user_id', $user->id)->orderBy('created_at', 'desc')->paginate();
        return RemitterResource::collection($remitters);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fullname' => 'required',
            'document_type' => 'required',
            'dni' => 'required',
            'issuance_date' => 'required',
            'expiration_date' => 'required',
            'dob' => 'required',
            'issuance_country_id' => 'required|exists:countries,id',
            'country_id' => 'required|exists:countries,id',
            'phone' => 'required',
        ]);

        $remitter = Remitter::create([
            'user_id' => Auth::id(),
            'fullname' => $request->fullname,
            'document_type' => $request->document_type,
            'dni' => $request->dni,
            'issuance_date' => $request->issuance_date,
            'expiration_date' => $request->expiration_date,
            'dob' => $request->dob,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'country_id' => $request->country_id,
            'issuance_country_id' => $request->country_id,
            'phone' => $request->phone,
            'email' => $request->email,
        ]);

        return new RemitterResource($remitter);
    }

    public function show(Remitter $remitter)
    {
        if ($remitter->user_id == Auth::id()) {
            return new RemitterResource($remitter);
        }
        return response('Forbidden', Response::HTTP_FORBIDDEN);
    }

    public function update(Request $request, Remitter $remitter)
    {
        if ($remitter->user_id != Auth::id()) {
            return response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'fullname' => 'required',
            'document_type' => 'required',
            'dni' => 'required',
            'issuance_date' => 'required',
            'expiration_date' => 'required',
            'dob' => 'required',
            'issuance_country_id' => 'required|exists:countries,id',
            'country_id' => 'required|exists:countries,id',
            'phone' => 'required',
        ]);

        $remitter->fullname = $request->input('fullname', $remitter->fullname);
        $remitter->document_type = $request->input('document_type', $remitter->document_type);
        $remitter->dni = $request->input('dni', $remitter->dni);
        $remitter->issuance_date = $request->input('issuance_date', $remitter->issuance_date);
        $remitter->expiration_date = $request->input('expiration_date', $remitter->expiration_date);
        $remitter->dob = $request->input('dob', $remitter->dob);
        $remitter->address = $request->input('address', $remitter->address);
        $remitter->city = $request->input('city', $remitter->city);
        $remitter->state = $request->input('state', $remitter->state);
        $remitter->country_id = $request->input('country_id', $remitter->country_id);
        $remitter->issuance_country_id = $request->input('country_id', $remitter->country_id);
        $remitter->phone = $request->input('phone', $remitter->phone);
        $remitter->email = $request->input('email', $remitter->email);
        $remitter->save();

        return new RemitterResource($remitter);
    }

    public function destroy(Remitter $remitter)
    {
        if($remitter->user_id == Auth::id()) {
            if (count($remitter->orders)>0) {
                return response([
                    "message" => "Cannot delete the selected recipient.",
                    "errors" => [
                        "recipient" => [
                            "This recipient has orders created."
                        ],
                    ]
                ], 422);
            }
            $remitter->delete();
            return new RemitterResource($remitter);
        }
        return response('Forbidden', Response::HTTP_FORBIDDEN);
    }
}
