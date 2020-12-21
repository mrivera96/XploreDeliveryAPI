<?php

namespace App\Http\Controllers;

use App\Label;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LabelsController extends Controller
{
    public function getLabels()
    {
        
        try {
            $myLabels = Label::where('idCliente', Auth::user()->idCliente)->get();

            return response()
                ->json([
                    'error' => 0,
                    'data' => $myLabels
                ], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al agregar la nueva etiqueta. Intenta nuevamente.'
            ], 500);
        }
    }

    public function createLabel(Request $request)
    {
        $request->validate([
            'descEtiqueta' => 'required',
        ]);
        try {
            $nLabel = new Label();
            $nLabel->descEtiqueta = $request->descEtiqueta;
            $nLabel->idCliente = Auth::user()->idCliente;
            $nLabel->fechaRegistro = Carbon::now();
            $nLabel->save();

            return response()
                ->json([
                    'error' => 0,
                    'message' => 'Etiqueta agregada correctamente.'
                ], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al agregar la nueva etiqueta. Intenta nuevamente.'
            ], 500);
        }
    }

    public function deleteLabel(Request $request)
    {
        $request->validate(['idEtiqueta' => 'required']);
        try {
            Label::where('idEtiqueta', $request->idEtiqueta)
            ->delete();

            return response()
                ->json([
                    'error' => 0,
                    'message' => 'Etiqueta eliminada correctamente.'
                ], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al eliminar la etiqueta. Intenta nuevamente.'
            ], 500);
        }
    }

    public function updateLabel(Request $request)
    {
        $request->validate([
            'idEtiqueta' => 'required',
            'descEtiqueta' => 'required',
        ]);
        try {
            Label::where('idEtiqueta', $request->idEtiqueta)
            ->update([
                'descEtiqueta' => $request->descEtiqueta,
            ]);

            return response()
                ->json([
                    'error' => 0,
                    'message' => 'Etiqueta actualizada correctamente.'
                ], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al actualizar la etiqueta. Intenta nuevamente.'
            ], 500);
        }
    }
}
