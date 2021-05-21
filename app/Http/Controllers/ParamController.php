<?php

namespace App\Http\Controllers;

use App\Models\Param;
use Illuminate\Http\Request;
use App\Http\Resources\ParamResource;

class ParamController extends Controller
{

    public function index()
    {
        $params = Param::all();
        return ParamResource::collection($params);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required',
            'label' => 'required',
            'value_type'    => 'nullable|in:string,integer,decimal,array,boolean'
        ]);

        $param = Param::create([
            'name'          => $request->input('name'),
            'label'         => $request->input('label'),
            'description'   => $request->input('description'),
            'value'         => $request->input('value'),
            'value_type'    => $request->input('value_type'),
            'default_value' => $request->input('default_value'),
        ]);

        return new ParamResource($param);
    }

    public function show(Param $param)
    {
        return new ParamResource($param);
    }

    public function update(Request $request, Param $param)
    {
        $request->validate([
            'name'  => 'required',
            'label' => 'required',
            'value_type'    => 'nullable|in:string,integer,decimal,array,boolean'
        ]);

        $param->name = $request->input('name', $param->name);
        $param->label = $request->input('label', $param->label);
        $param->description = $request->input('description', $param->description);
        $param->value = $request->input('value', $param->value);
        $param->value_type = $request->input('value_type', $param->value_type);
        $param->default_value = $request->input('default_value', $param->default_value);
        $param->save();

        return new ParamResource($param);
    }

    public function destroy(Param $param)
    {
        $param->delete();
        return new ParamResource($param);
    }
}
