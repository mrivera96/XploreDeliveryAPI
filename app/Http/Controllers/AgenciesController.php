<?php

namespace App\Http\Controllers;

use App\City;
use Exception;
use Illuminate\Support\Facades\Log;

class AgenciesController extends Controller
{
    public function listCities(){
        try{
            $cities = City::where('isActivo', 1)->get();
            return response()->json([
                'error' => 0,
                'data' => $cities
            ], 200);
        }catch (Exception $ex){
            Log::error($ex->getMessage(),['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => $ex->getMessage()
            ], 500);
        }
    }
}
