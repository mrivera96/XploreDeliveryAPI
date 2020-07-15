<?php

namespace App\Http\Controllers;

use App\ExtraCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ExtraChargesController extends Controller
{
    public function get(){
        try {
            $extraCharges = ExtraCharge::all();

            return response()
                ->json([
                    'error' => 0,
                    'data' => $extraCharges
                ],
                    200);

        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), array(
                'User' => Auth::user()->nomUsuario,
                'context' => $ex->getTrace()));
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al cargar los datos'
                ],
                500
            );
        }
    }

    public function create(Request $request){
        $request->validate([
            'form' => 'required',
            'form.nombre' => 'required',
            'form.costo' => 'required'
        ]);

        try {
            $rNom = $request->form['nombre'];
            $rCost = $request->form['costo'];

            $nEC = new ExtraCharge();

            $nEC->nombre = $rNom;
            $nEC->costo = $rCost;
            $nEC->save();

            return response()
                ->json([
                    'error' => 0,
                    'message' => 'Cargo extra agregado correctamente.'
                ],
                    200);

        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), array(
                'User' => Auth::user()->nomUsuario,
                'context' => $ex->getTrace()));
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al agregar el cargo extra'
                ],
                500
            );
        }
    }

    public function update(Request $request){
        $request->validate([
            'ecId' => 'required',
            'form' => 'required',
            'form.nombre' => 'required',
            'form.costo' => 'required',

        ]);

        try {
            $rNom = $request->form['nombre'];
            $rCost = $request->form['costo'];
            $aEcId = $request->ecId;

            $aEC = ExtraCharge::where('idCargoExtra', $aEcId);

            $aEC->update([
                'nombre' => $rNom,
                'costo' => $rCost
            ]);

            return response()
                ->json([
                    'error' => 0,
                    'message' => 'Cargo extra actualizado correctamente.'
                ],
                    200);

        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), array(
                'User' => Auth::user()->nomUsuario,
                'context' => $ex->getTrace()));
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al actualizar el cargo extra'
                ],
                500
            );
        }
    }
}
