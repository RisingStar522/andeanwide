<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'recipient_id'      => 'required|exists:recipients,id',
            'pair_id'           => 'required|exists:pairs,id',
            'priority_id'       => 'required|exists:priorities,id',
            'payment_amount'    => 'required|numeric',
            'rate'              => 'required|numeric',
            'remitter_id'       => 'nullable|exists:remitters,id'
        ];
    }
}
