<?php

namespace App\Http\Controllers;

use App\BillingFrequency;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingFrequenciesController extends Controller
{
    public function getBillingFrequencies(){
        try {
            $billFreq = BillingFrequency::where('isActivo',1)->get();
            return response()->json(
                [
                    'error' => 0,
                    'data' => $billFreq
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
