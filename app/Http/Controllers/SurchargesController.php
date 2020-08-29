<?php

namespace App\Http\Controllers;

use App\RecargoDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SurchargesController extends Controller
{
    public function getSurcharges()
    {
        try {
            $recargos = RecargoDelivery::with(['customer','category'])->get();
            foreach ($recargos as $recargo) {
                $recargo->monto = number_format($recargo->monto, 2);
                $recargo->kilomMinimo = number_format($recargo->kilomMinimo, 2);
                $recargo->kilomMaximo = number_format($recargo->kilomMaximo, 2);
            }
            return response()->json(
                [
                    'error' => 0,
                    'data' => $recargos
                ],
                200
            );
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(),array('User' => Auth::user()->nomUsuario,
                'context' => $ex->getTrace()));
            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
        }
    }

    public function getCustomerSurcharges()
    {
        try {
            $currCustomer = Auth::user()->idCliente;
            $recargos = RecargoDelivery::where('idCliente', $currCustomer)->get();

            if ($recargos->count() == 0) {
                $recargos = RecargoDelivery::where('idCliente', 1)->get();
            }
            foreach ($recargos as $recargo) {
                $recargo->kilomMinimo = number_format($recargo->kilomMinimo, 2);
                $recargo->kilomMaximo = number_format($recargo->kilomMaximo, 2);
                $recargo->monto = number_format($recargo->monto, 2);
            }
            return response()->json(
                [
                    'error' => 0,
                    'data' => $recargos
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

    public function updateSurcharge(Request $request)
    {
        $idSurcharge = $request->form["idRecargo"];
        $desc = $request->form["descRecargo"];
        $klmin = $request->form["kilomMinimo"];
        $klmax = $request->form["kilomMaximo"];
        $monto = $request->form["monto"];
        $cliente = $request->form["idCliente"];
        $category = $request->form["idCategoria"];
        try {
            $currRate = RecargoDelivery::where('idRecargo', $idSurcharge);
            $currRate->update([
                    'descRecargo' => $desc,
                    'kilomMinimo' => $klmin,
                    'kilomMaximo' => $klmax,
                    'monto' => $monto,
                    'idCliente' => $cliente,
                    'idCategoria' => $category]
            );

            return response()->json([
                'error' => 0,
                'message' => 'Recargo actualizado correctamente.'
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al actualizar el recargo.'
            ], 500);
        }
    }

    public function createSurcharge(Request $request)
    {
        $desc = $request->form["descRecargo"];
        $klmin = $request->form["kilomMinimo"];
        $klmax = $request->form["kilomMaximo"];
        $monto = $request->form["monto"];
        $cliente = $request->form["idCliente"];
        $category = $request->form["idCategoria"];
        try {
            $currsurcharge = new RecargoDelivery();
            $currsurcharge->descRecargo = $desc;
            $currsurcharge->kilomMinimo = $klmin;
            $currsurcharge->kilomMaximo = $klmax;
            $currsurcharge->monto = $monto;
            $currsurcharge->idCliente = $cliente;
            $currsurcharge->idCategoria = $category;
            $currsurcharge->save();

            return response()->json([
                'error' => 0,
                'message' => 'Recargo agregado correctamente.'
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al agregar el recargo.'
            ], 500);
        }
    }
}
