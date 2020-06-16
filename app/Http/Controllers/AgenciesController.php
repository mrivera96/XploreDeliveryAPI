<?php

namespace App\Http\Controllers;

use App\Agency;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgenciesController extends Controller
{
    public function listAgencies(){
        try{
            $agencies = Agency::where('isActivo', 1)->get();
            return response()->json([
                'error' => 0,
                'data' => $agencies
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
