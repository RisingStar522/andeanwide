<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\Identity;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OtrosPagosController extends Controller
{

    /**
     * Funcion que va a llamar el servicio de otrospagos
     * para consultar la deuda de los clientes
    */
    public function condeu01req(Request $request)
    {
        //print_r($request->all());
        $identity = Identity::where('identity_number',$request->all()['p_idcli'] )->first();
        $orders = Order::where('user_id', $identity->user_id)->get();

        $response_orders = array();
        
        for($x = 0; $x < count($orders) ; $x++) {
            $doc = [
                    'r_doc' => $orders[$x]['payment_code'],
                    'r_mnt' => str_pad($orders[$x]['payment_amount']*100, 12, "0", STR_PAD_LEFT),
                    'r_mnv' => '000000000000',
                    'r_fve' => '20220101', //$orders[$x]['payment_code'],
                    'r_fem' => '20220101', //$orders[$x]['payment_code'],
                    'r_des' => 'REF: 000001'
            ];
            array_push($response_orders, $doc);
        }

        $response_obj = [
            'r_tid'  => $request->all()['p_tid'],
            'r_retcod' =>  count($orders) > 0 ? '00' : '03',
            'r_ndoc' => str_pad(count($orders), 4, "0", STR_PAD_LEFT),
            'r_docs' => [$response_orders ]
        ];
        return response(json_encode($response_obj))->header('Content-Type', 'application/javascript');
    }


    /**
     * Funcion que va a llamar el servicio de otrospagos
     * cuando el usuario realice el pago exitosamente 
    */
    public function notpag01req(Request $request)
    {
        $order = Order::where('payment_code', $request->all()['p_doc'])->first();

        $order->verified_at = Carbon::now();
        $order->payment_method = 'OtrosPagos';
        $order->save();

        $response_obj = [
            'r_tid'  => $request->all()['p_tid'],
            'r_retcod' =>  '00',
            'r_cau' => strtoupper(Str::random(8))
        ];
        return response(json_encode($response_obj))->header('Content-Type', 'application/javascript');;
    }
}