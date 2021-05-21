<?php

namespace App\Http\Controllers;

use App\Models\Recipient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Resources\RecipientResource;

use function PHPUnit\Framework\isNull;

class RecipientController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if($request->query() && !$request->query('page')) {
            $recipients = Recipient::where('user_id', $user->id);
            foreach ($request->query() as $key => $value) {
                $recipients->where($key, 'like', '%' . $value . '%');
            }
            return RecipientResource::collection($recipients->get());
        }
        $recipients = Recipient::where('user_id', $user->id)->orderBy('created_at', 'desc')->paginate();
        return RecipientResource::collection($recipients);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'          => 'required',
            'lastname'      => 'required',
            'dni'           => 'required',
            'country_id'    => 'required|exists:countries,id',
            'dni'           => 'required',
            'email'         => 'nullable|email',
            'bank_id'       => 'required|exists:banks,id',
            'document_type' => 'required'
        ]);

        $recipient = Recipient::create([
            'user_id'       => $user->id,
            'name'          => $request->input('name'),
            'lastname'      => $request->input('lastname'),
            'dni'           => $request->input('dni'),
            'country_id'    => $request->input('country_id'),
            'bank_id'       => $request->input('bank_id'),
            'phone'         => $request->input('phone'),
            'email'         => $request->input('email'),
            'bank_name'     => $request->input('bank_name'),
            'bank_account'  => $request->input('bank_account'),
            'account_type'  => $request->input('account_type'),
            'bank_code'     => $request->input('bank_code'),
            'address'       => $request->input('address'),
            'document_type' => $request->input('document_type')
        ]);

        return new RecipientResource($recipient);
    }

    public function show(Request $request, Recipient $recipient)
    {
        $user = $request->user();
        if($user->hasRole('user') && $recipient->user_id != $user->id) {
            return response('Forbidden', Response::HTTP_FORBIDDEN);
        }
        return new RecipientResource($recipient);
    }

    public function update(Request $request, Recipient $recipient)
    {
        $user = $request->user();
        if($user->hasRole('user') && $recipient->user_id != $user->id) {
            return response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'email' => 'nullable|email'
        ]);

        $recipient->document_type = $request->input('document_type', $recipient->document_type);
        $recipient->dni = $request->input('dni', $recipient->dni);
        $recipient->email = $request->input('email', $recipient->email);
        $recipient->phone = $request->input('phone', $recipient->phone);
        $recipient->address = $request->input('address', $recipient->address);
        $recipient->bank_id = $request->input('bank_id', $recipient->bank_id);
        $recipient->account_type = $request->input('account_type', $recipient->account_type);
        $recipient->bank_account = $request->input('bank_account', $recipient->bank_account);
        $recipient->save();

        return new RecipientResource($recipient);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Recipient  $recipient
     * @return \Illuminate\Http\Response
     */
    public function destroy(Recipient $recipient)
    {
        if (count($recipient->orders)>0) {
            return response([
                "message" => "Cannot delete the selected recipient.",
                "errors" => [
                    "recipient" => [
                        "This recipient has orders created."
                    ],
                ]
            ], 422);
        }
        $recipient->delete();
        return new RecipientResource($recipient);
    }
}
