<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Identity;
use Illuminate\Http\Request;
use App\Actions\Helpers\TransactionAction;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\Response;

class BalanceController extends Controller
{
    public function createIncome(Request $request)
    {
        $request->validate([
            'external_id' => 'required|exists:accounts,id',
            'transaction_date' => 'required|date|after_or_equal:yesterday|before:tomorrow',
            'payin_id' => 'required',
            'origin_id' => 'required',
            'amount' => 'required',
            'authorization' => 'required'
        ]);

        $account = Account::find($request->external_id);

        $payload = $request['external_id'] . $request['transaction_date'] . $request['payin_id'] . $request['origin_id'] . $request['amount'];
        $hashed_authorization = hash_hmac('sha256', $payload, $account->secret_key);
        if($request->authorization == $hashed_authorization) {
            $identity = Identity::where([
                 'identity_number' => $request->origin_id,
                 'document_type' => $request->input('document_type', 'dni')
            ])->first();

            $transaction = null;
            if($identity) {
                $user = $identity->user;
                $now = now()->toAtomString();
                $account_name = $account->name;
                $note = "Transacción creada el $now, en $account_name por $user->username por el monto de $request->amount].";
                $transaction = TransactionAction::createIncomeTransaction($user, $account, $request->payin_id, $request->amount, $note, $request->transaction_date);
            }

            return new TransactionResource($transaction);
        }
        return response('Forbiden', Response::HTTP_FORBIDDEN);
    }
}
