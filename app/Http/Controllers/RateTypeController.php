<?php

namespace App\Http\Controllers;

use App\RateType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RateTypeController extends Controller
{
    public function get()
    {
        try {
            $rateTypes = RateType::where('isActivo', 1)->get();
            return response()
                ->json(
                    [
                        'error' => 0,
                        'data' => $rateTypes
                    ],
                    200
                );
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ha ocurrido un error al cargar los datos'
                ],
                500
            );
        }
    }
}
