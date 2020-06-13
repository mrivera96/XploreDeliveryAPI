<?php

namespace App\Http\Controllers;

use App\Vehiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VehiclesController extends Controller
{
    public function list(){
        try {
            $vehiculos = Vehiculo::where('idEstado',5)->get();
            return response()->json([
                'error'=>0,
                'data'=>$vehiculos]
            ,200);
        }catch (\Exception $exception){
            Log::error($exception->getMessage(),['context' => $exception->getTrace()]);
            return response()->json([
                    'error'=>1,
                    'message'=>$exception->getMessage()]
                ,500);
        }


    }
}
