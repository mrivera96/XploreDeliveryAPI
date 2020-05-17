<?php

namespace App\Http\Controllers;

use App\Vehiculo;
use Illuminate\Http\Request;

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
            return response()->json([
                    'error'=>1,
                    'message'=>$exception->getMessage()]
                ,500);
        }


    }
}
