<?php

namespace App\Http\Controllers;

use App\ServiceType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServiceTypesController extends Controller
{
    public function getServiceTypes(){
        try {
            $servTypes = ServiceType::where('isActivo',1)->get();
            return response()->json(
                [
                    'error' => 0,
                    'data' => $servTypes
                ],
                200
            );

        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al consultar los datos'
                ],
                500
            );
        }
    }
}
