<?php

namespace App\Http\Controllers;

use App\Estado;
use Illuminate\Http\Request;

class StatesController extends Controller
{
    public function list()
    {
        try {
            $states = Estado::where('isActivo',1)->where('idTipoEstado', 8)->get();

            return response()->json([
                'error' => 0,
                'data' => $states
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
