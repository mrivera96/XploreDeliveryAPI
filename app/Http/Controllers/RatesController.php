<?php

namespace App\Http\Controllers;

use App\Tarifa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RatesController extends Controller
{
    public function getRates()
    {
        try {
            $tarifas = Tarifa::all();
            foreach ($tarifas as $tarifa) {
                $tarifa->category;
                $tarifa->precio = number_format($tarifa->precio, 2);
                $tarifa->cliente = $tarifa->customer;
            }
            return response()->json(
                [
                    'error' => 0,
                    'data' => $tarifas
                ],
                200
            );
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(),['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
        }
    }

    public function getCustomerRates()
    {
        try {
            $currCustomer = Auth::user()->idCliente;
            $tarifas = Tarifa::where('idCliente', $currCustomer)->get();

            if($tarifas->count() == 0){
                $tarifas = Tarifa::where('idCliente', 1)->get();
            }
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
            Log::error($ex->getMessage(),['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
        }
    }

    public function updateRate(Request $request)
    {
        $idRate = $request->form["idTarifaDelivery"];
        $descRate = $request->form["descTarifa"];
        $idCategoria = $request->form["idCategoria"];
        $emin = $request->form["entregasMinimas"];
        $emax = $request->form["entregasMaximas"];
        $monto = $request->form["precio"];
        $cliente = $request->form["idCliente"];
        try {
            $currRate = Tarifa::where('idTarifaDelivery', $idRate);
            $currRate->update([
                'idCategoria' => $idCategoria,
                'descTarifa' => $descRate,
                'entregasMinimas' => $emin,
                'entregasMaximas' => $emax,
                'precio' => $monto,
                'idCliente' => $cliente]
            );

            return response()->json([
                'error' => 0,
                'message' => 'Tarifa actualizada correctamente.'
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(),['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al actualizar la tarifa.'
            ], 500);
        }
    }

    public function createRate(Request $request)
    {
        $descRate = $request->form["descTarifa"];
        $idCategoria = $request->form["idCategoria"];
        $emin = $request->form["entregasMinimas"];
        $emax = $request->form["entregasMaximas"];
        $monto = $request->form["precio"];
        $cliente = $request->form["idCliente"];
        try {
            $currRate = new Tarifa();
            $currRate->descTarifa = $descRate;
            $currRate->idCategoria = $idCategoria;
            $currRate->entregasMinimas = $emin;
            $currRate->entregasMaximas = $emax;
            $currRate->precio = $monto;
            $currRate->idCliente = $cliente;
            $currRate->save();

            return response()->json([
                'error' => 0,
                'message' => 'Tarifa agregada correctamente.'
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(),['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al agregar la tarifa.'
            ], 500);
        }
    }
}
