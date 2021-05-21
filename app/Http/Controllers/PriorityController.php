<?php

namespace App\Http\Controllers;

use App\Http\Resources\PriorityResource;
use App\Models\Priority;
use Illuminate\Http\Request;

class PriorityController extends Controller
{
    public function index()
    {
        $priorities = Priority::where('is_active', true)->get();
        return PriorityResource::collection($priorities);
    }

    public function adminIndex()
    {
        $priorities = Priority::all();
        return PriorityResource::collection($priorities);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required',
            'label'     => 'required',
            'cost_pct'  => 'numeric'
        ]);

        $priority = Priority::create([
            'name'          => $request->input('name'),
            'label'         => $request->input('label'),
            'sublabel'      => $request->input('sublabel'),
            'description'   => $request->input('description'),
            'cost_pct'      => $request->input('cost_pct'),
            'is_active'     => true
        ]);

        return new PriorityResource($priority);
    }

    public function show(Priority $priority)
    {
        return new PriorityResource($priority);
    }

    public function update(Request $request, Priority $priority)
    {
        $request->validate([
            'name'      => 'required',
            'label'     => 'required',
            'cost_pct'  => 'numeric'
        ]);

        $priority->name = $request->input('name', $priority->name);
        $priority->label = $request->input('label', $priority->label);
        $priority->sublabel = $request->input('sublabel', $priority->sublabel);
        $priority->description = $request->input('description', $priority->description);
        $priority->cost_pct = $request->input('cost_pct', $priority->cost_pct);
        $priority->is_active = $request->input('is_active', $priority->is_active);
        $priority->save();

        return new PriorityResource($priority);
    }

    public function destroy(Priority $priority)
    {
        $priority->delete();
        return new PriorityResource($priority);
    }
}
