<?php

namespace App\Http\Controllers;

use App\Tarifa;
use Illuminate\Http\Request;

class RatesController extends Controller
{
    public function getRates()
    {
        try {
            $tarifas = Tarifa::all();
            foreach ($tarifas as $tarifa) {
                $tarifa->category;
                $tarifa->precio = number_format($tarifa->precio, 2);
            }
            return response()->json(
                [
                    'error' => 0,
                    'data' => $tarifas
                ],
                200
            );
        } catch (\Exception $ex) {
            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
        }
    }

    public function updateRate(Request $request){
        $idRate = $request->form["idTarifaDelivery"];
        $idCategoria = $request->form["idCategoria"];
        $emin = $request->form["entregasMinimas"];
        $emax = $request->form["entregasMaximas"];
        $monto = $request->form["precio"];
        try {
            $currRate = Tarifa::where('idTarifaDelivery', $idRate);
            $currRate->update([
                'idCategoria' => $idCategoria,
                'entregasMinimas' => $emin,
                'entregasMaximas' => $emax,
                'precio' => $monto]);

            return response()->json([
                'error' => 0,
                'message' => 'Tarifa actualizada correctamente.'
            ],200);
        }catch (Exception $ex){
            return response()->json([
                'error' => 1,
                'message' => 'Error al actualizar la tarifa. '.$ex->getMessage()
            ],500);
        }
    }
}
