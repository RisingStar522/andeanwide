<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\UserResource;

class CompanyController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        if(Auth::user()->account_type === 'personal') {
            return response('Forbidden', Response::HTTP_FORBIDDEN);
        } else if (isset($user->company) && isset($user->company->isRejected)) {
            $user->company->delete();
        }

        $request->validate([
            'name' => 'required',
            'id_number' => 'required',
            'activity' => 'required',
            'country_id' => 'required|exists:countries,id',
            'politician_history_country_id' => 'nullable|exists:countries,id',
            'anual_revenues' => 'nullable|in:LT_100M_USD,LT_1MM_USD,LT_4MM_USD,GT_4MM_USD',
            'company_size' => 'nullable|in:micro,small,mid,large',
        ]);

        Company::create([
            'user_id' => Auth::id(),
            'name' => $request->input('name'),
            'id_number' => $request->input('id_number'),
            'activity' => $request->input('activity'),
            'country_id' => $request->input('country_id'),
            'address' => $request->input('address'),
            'has_politician_history' => $request->input('has_politician_history', false),
            'politician_history_charge' => $request->input('politician_history_charge'),
            'politician_history_country_id' => $request->input('politician_history_country_id'),
            'politician_history_from' => $request->input('politician_history_from'),
            'politician_history_to' => $request->input('politician_history_to'),
            'activities' => $request->input('activities'),
            'anual_revenues' => $request->input('anual_revenues'),
            'company_size' => $request->input('company_size'),
            'fund_origins' => $request->input('fund_origins'),
        ]);

        return response(new UserResource($user), 201);
    }

    public function verifyCompany(Request $request, User $user)
    {
        if ($user->account_type === 'personal') {
            return response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        if (is_null($user->company)) {
            return response('Not Found', Response::HTTP_NOT_FOUND);
        }

        $user->company->verified_at = now();
        $user->company->save();

        return new UserResource($user);

    }

    public function rejectCompany(Request $request, User $user)
    {
        if ($user->account_type === 'personal') {
            return response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        if (is_null($user->company)) {
            return response('Not Found', Response::HTTP_NOT_FOUND);
        }

        $user->company->rejected_at = now();
        $user->company->rejection_reasons = $request->rejection_reasons;
        $user->company->save();

        return new UserResource($user);
    }
}
