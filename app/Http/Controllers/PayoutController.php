<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Str;
use App\Models\DLocalConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\OrderResource;
use App\Models\PayoutRequest;

class PayoutController extends Controller
{
    public function createDLocalPayout(Request $request, Order $order)
    {
        if (!$order->canPayoutOrder) {
            return response([
                "message" => "Can not send a payout for this order.",
                "errors" => [
                    "order_id" => [
                        "It is not allow to send a payout order."
                    ],
                ]
            ], Response::HTTP_FORBIDDEN);
        }

        $dLocalConfig = $this->getDLocalConfig();
        $requestData = [
            'order_id'              => $order->id,
            'external_id'           => 'AW' . Str::padLeft($order->id, 6, '0'),
            'document_id'           => $order->recipient->dni,
            'document_type'         => $order->recipient->document_type,
            'beneficiary_name'      => $order->recipient->name,
            'beneficiary_lastname'  => $order->recipient->lastname,
            'country'               => $order->recipient->country->abbr,
            'bank_code'             => $order->recipient->bank->code,
            'bank_name'             => $order->recipient->bank->name,
            'bank_account'          => $order->recipient->bank_account,
            'account_type'          => $order->recipient->account_type,
            'amount'                => number_format($order->received_amount, 2, ".", ""),
            'address'               => $order->recipient->address,
            'currency'              => $order->pair->quote->symbol,
            'email'                 => $order->recipient->email,
            'phone'                 => $order->recipient->phone,
            'purpose'               => $order->purpose,
            'remitter_fullname'     => $order->remitter ? $order->remitter->fullname : ($order->user->identity ? $order->user->identity->firstname . ' ' . $order->user->identity->lastname : ''),
            'remitter_document'     => $order->remitter ? $order->remitter->document : ($order->user->identity ? $order->user->identity->identity_number : ''),
            'remitter_address'      => $order->remitter ? $order->remitter->address : ($order->user->address ? $order->user->address->address : ''),
            'remitter_city'         => $order->remitter ? $order->remitter->city : ($order->user->city ? $order->user->address->city : ''),
            'remitter_country'      => $order->remitter ? $order->remitter->country->abbr : ($order->user->address ? $order->user->address->country->abbr : ''),
            'notification_url'      => config('app.url') . '/api/orders/comments'
        ];

        $response = Http::withToken($dLocalConfig->access_token)
            ->post($dLocalConfig->url . '/api/payout/cashout-request', $requestData);

        $requestData = array_merge($requestData, ['request_url' => $dLocalConfig->url . '/api/payout/cashout-request']);
        PayoutRequest::create($requestData);

        if($response->successful()) {
            $data = $response->json();
            if($data['status'] == 0) {
                $order->payed_at = now();
                $order->payout_id = $data['cashout_id'];
                $order->payout_status = 'Received';
                $order->payout_status_code = '0';
                $order->save();
                return new OrderResource($order);
            }
            return response([
                "message" => $data['desc'],
                "errors" => [
                    "payout" => [
                        $data['desc']
                    ],
                ]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        return response($response->json(), $response->status());
    }

    public function checkPayoutStatus(Request $request, Order $order)
    {
        if (is_null($order->payed_at) && is_null($order->payout_id)) {
            return response([
                "message" => "Can not check the status of this payout.",
                "errors" => [
                    "order_id" => [
                        "Payout does not exists."
                    ],
                ]
            ], Response::HTTP_FORBIDDEN);
        }

        $dLocalConfig = $this->getDLocalConfig();
        $response = Http::withToken($dLocalConfig->access_token)
            ->post($dLocalConfig->url . '/api/payout/check-status', [
                'external_id' => 'AW' . Str::padLeft($order->id, 6, '0'),
                'cashout_id' => $order->payout_id
            ]);

        if($response->successful()) {
            $data = $response->json();
            $order->payout_status = $data['description'];
            $order->payout_status_code = $data['status'];
            if($data['status'] === '1') {
                $order->completed_at = now();
            }
            $order->save();
            return new OrderResource($order);
        }
        return response($response->json(), $response->status());
    }

    public function cancelPayout(Request $request, Order $order)
    {
        $request->validate([
            'observation' => 'required'
        ]);

        $dLocalConfig = DLocalConfig::where('name', 'api.andeanwide.com')->first();
        if (is_null($order->payed) && is_null($order->payout_id) && is_null($order->completed_at)) {
            return response([
                "message" => "Can not cancel the status of this payout.",
                "errors" => [
                    "order_id" => [
                        "Can not cancel the selected payout order."
                    ],
                ]
            ], Response::HTTP_FORBIDDEN);
        }

        $dLocalConfig = $this->getDLocalConfig();
        $response = Http::withToken($dLocalConfig->access_token)
            ->post($dLocalConfig->url . '/api/payout/cancel-cashout', [
                'external_id' => 'AW' . Str::padLeft($order->id, 6, '0'),
                'cashout_id' => $order->payout_id
            ]);

        if($response->successful()) {
            $order->payout_status = 'Cancelled';
            $order->payout_status_code = '2';
            $order->rejected_at = now();
            $order->rejection_reason = $request->observation;
            $order->save();
            return new OrderResource($order);
        }
        return response($response->json(), $response->status());
    }

    protected function getDLocalConfig()
    {
        $dLocalConfig = DLocalConfig::where('name', 'api.andeanwide.com')->first();
        if(!$dLocalConfig) {
            return response([
                "message" => "The dlocal service is not configured.",
                "errors" => [
                    "api.andeanwide.com" => [
                        "You must setup the dlocal service for api.andeanwide.com properly."
                    ],
                ]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } else if (!$dLocalConfig->access_token) {
            return response([
                "message" => "The dlocal service access token has not been setup.",
                "errors" => [
                    "access_token" => [
                        "The access token field has not been set yet."
                    ],
                ]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $dLocalConfig;
    }
}
