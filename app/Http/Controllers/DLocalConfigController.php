<?php

namespace App\Http\Controllers;

use App\Models\DLocalConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\DLocalConfigResource;

class DLocalConfigController extends Controller
{
    public function index()
    {
        return DLocalConfigResource::collection(DLocalConfig::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:100',
            'url' => 'required|max:100',
            'client_id' => 'required|numeric',
            'grant_type' => 'nullable',
            'access_token' => 'nullable',
        ]);

        $dLocalConfig = DLocalConfig::create([
            'name' => $request->input('name'),
            'url' => $request->input('url'),
            'client_id' => $request->input('client_id'),
            'client_secret' => $request->input('client_secret'),
            'grant_type' => $request->input('grant_type', 'client_credentials'),
        ]);

        return new DLocalConfigResource($dLocalConfig);
    }

    public function show(DLocalConfig $dLocalConfig)
    {
        return new DLocalConfigResource($dLocalConfig);
    }

    public function update(Request $request, DLocalConfig $dLocalConfig)
    {
        $request->validate([
            'name' => 'max:100',
            'url' => 'max:100',
            'client_id' => 'numeric',
            'grant_type' => 'nullable',
            'access_token' => 'nullable',
        ]);

        $dLocalConfig->name = $request->input('name', $dLocalConfig->name);
        $dLocalConfig->url = $request->input('url', $dLocalConfig->url);
        $dLocalConfig->client_id = $request->input('client_id', $dLocalConfig->client_id);
        $dLocalConfig->client_secret = $request->input('client_secret', $dLocalConfig->client_secret);
        $dLocalConfig->grant_type = $request->input('grant_type', $dLocalConfig->grant_type);
        $dLocalConfig->save();

        return new DLocalConfigResource($dLocalConfig);
    }

    public function destroy(DLocalConfig $dLocalConfig)
    {
        $dLocalConfig->delete();
        return new DLocalConfigResource($dLocalConfig);
    }

    public function setAccessToken(Request $request,  DLocalConfig $dLocalConfig)
    {
        $response = Http::post($dLocalConfig->url . 'oauth/token', [
            'grant_type' => $dLocalConfig->grant_type,
            'client_id' => $dLocalConfig->client_id,
            'client_secret' => $dLocalConfig->client_secret,
            'score' => '*',
        ]);

        if ($response->successful()) {
            $dLocalConfig->access_token = $response->json()['access_token'];
            $dLocalConfig->save();
            return new DLocalConfigResource($dLocalConfig);
        }

        return response([
            "message" => "The given data was invalid.",
            "errors" => [
                "client" => [
                    "The client secret field must be configured."
                ],
            ]
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
