<?php

namespace App\Http\Controllers;

use App\ExtraCharge;
use App\ExtraChargeCategory;
use Carbon\Carbon;
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
            'form.costo' => 'required',
            'form.tipoCargo' => 'required',
            'categories' => 'required'
        ]);

        $rNom = $request->form['nombre'];
        $rCost = $request->form['costo'];
        $rTypeCharg = $request->form['tipoCargo'];
        $categories = $request->categories;

        try {
            
            $nEC = new ExtraCharge();

            $nEC->nombre = $rNom;
            $nEC->costo = $rCost;
            $nEC->tipoCargo = $rTypeCharg;
            $nEC->save();

            $lastIndex = ExtraCharge::query()->max('idCargoExtra');

            if (sizeof($categories) > 0) {
                for ($i = 0; $i < sizeof($categories); $i++) {
                    $existe = ExtraChargeCategory::where('idCargoExtra', $lastIndex)
                        ->where('idCategoria', $categories[$i]['idCategoria'])->count();

                    if ($existe == 0) {
                        $nExtraChargeCategory = new ExtraChargeCategory();
                        $nExtraChargeCategory->idCargoExtra = $lastIndex;
                        $nExtraChargeCategory->idCategoria = $categories[$i]['idCategoria'];  
                        $nExtraChargeCategory->fechaRegistro = Carbon::now();
                        $nExtraChargeCategory->save();
                    }
                }

            }

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
