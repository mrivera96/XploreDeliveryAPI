<?php

namespace App\Http\Controllers;

use App\Estado;
use Illuminate\Http\Request;

class StatesController extends Controller
{
    public function list()
    {
        try {
            $statesDelivery = Estado::where('isActivo',1)->where('idTipoEstado', 8)->get();
            $statesDeliveryEntregas = Estado::where('isActivo',1)->where('idTipoEstado', 9)->get();
            return response()->json([
                'error' => 0,
                'data' => array('xploreDelivery' => $statesDelivery, 'xploreDeliveryEntregas' =>$statesDeliveryEntregas)
            ],
                200);

        }catch (\Exception $ex){
            return response()->json([
                'error' => 1,
                'message' => $ex->getMessage()
            ],
                500);
        }
    }
}
