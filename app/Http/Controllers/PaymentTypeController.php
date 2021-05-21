<?php

namespace App\Http\Controllers;

use App\Models\PaymentType;
use Illuminate\Http\Request;
use App\Http\Resources\PaymentTypeResource;

class PaymentTypeController extends Controller
{
    public function index()
    {
        $paymentTypes = PaymentType::where('is_active', true)->get();
        return PaymentTypeResource::collection($paymentTypes);
    }

    public function adminIndex()
    {
        return PaymentTypeResource::collection(PaymentType::all());
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name'  => 'required',
            'label' => 'required'
        ]);

        $paymentType = PaymentType::create([
            'name'          => $request->name,
            'label'         => $request->label,
            'description'   => $request->description,
            'class_name'    => $request->class_name,
            'is_active'     => $request->input('is_active', true)
        ]);

        return new PaymentTypeResource($paymentType);
    }

    public function show(PaymentType $paymentType)
    {
        return new PaymentTypeResource($paymentType);
    }

    public function update(Request $request, PaymentType $paymentType)
    {
        $paymentType->description = $request->input('description', $paymentType->description);
        $paymentType->class_name = $request->input('class_name', $paymentType->class_name);
        $paymentType->label = $request->input('label', $paymentType->label);
        $paymentType->is_active = $request->input('is_active', true);
        $paymentType->save();

        return new PaymentTypeResource($paymentType);
    }

    public function destroy(PaymentType $paymentType)
    {
        $paymentType->delete();
        return new PaymentTypeResource($paymentType);
    }
}
