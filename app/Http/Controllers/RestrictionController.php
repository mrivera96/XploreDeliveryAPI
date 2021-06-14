<?php

namespace App\Http\Controllers;

use App\Restriction;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RestrictionController extends Controller
{
    public function get(): \Illuminate\Http\JsonResponse
    {
        try {
            $restrictions = Restriction::all();

            return response()
                ->json([
                    'error' => 0,
                    'data' => $restrictions
                ], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Ocurrió un error al consultar los datos.'
            ], 500);
        }
    }

    public function create(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'form' => 'required',
            'form.descripcion' => 'required',
        ]);
        try {
            $nRestriction = new Restriction();
            $nRestriction->description = $request->form['descripcion'];
            $nRestriction->valMinimo = $request->form['valMinimo'];
            $nRestriction->valMaximo = $request->form['valMaximo'];
            $nRestriction->isActivo = 1;
            $nRestriction->fechaRegistro = Carbon::now();
            $nRestriction->save();

            return response()
                ->json([
                    'error' => 0,
                    'message' => 'Restricción agregada correctamente.'
                ], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Ocurrió un error al registrar la restricción.'
            ], 500);
        }
    }

    public function update(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'form' => 'required',
            'form.idRestriccion' => 'required',
            'form.descripcion' => 'required',
        ]);
        try {
            $currRestriction = Restriction::where('idRestriccion', $request->form['idRestriccion']);
            $currRestriction->update([
                'descripcion' => $request->form['descripcion'],
                'valMinimo' => $request->form['valMinimo'],
                'valMaximo' => $request->form['valMaximo'],
            ]);

            return response()
                ->json([
                    'error' => 0,
                    'message' => 'Restricción actualizada correctamente.'
                ], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Ocurrió un error al actualizar la restricción.'
            ], 500);
        }
    }
}
