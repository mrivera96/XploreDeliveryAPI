<?php

namespace App\Http\Controllers;

use App\TermsConditions;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TermsConditionsController extends Controller
{
    public function get()
    {
        try {
            $termsConditions = TermsConditions::all();
            return response()
                ->json([
                    'error' => 0,
                    'data' => $termsConditions
                ], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Ocurrió un error al consultar los datos.'
            ], 500);
        }

    }

    public function create(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.descripcion' => 'required',
            'form.valor' => 'required',
            'form.negrita' => 'required',
            'form.cursiva' => 'required'
        ]);

        try {
            $rTermCond = $request->form;
            $newTermCondition = new TermsConditions();
            $newTermCondition->descripcion = $rTermCond['descripcion'];
            $newTermCondition->valor = $rTermCond['valor'];
            $newTermCondition->negrita = $rTermCond['negrita'];
            $newTermCondition->cursiva = $rTermCond['cursiva'];
            $newTermCondition->save();

            return response()
                ->json([
                    'error' => 0,
                    'message' => 'Se ha registrado correctamente.'
                ], 200);


        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => $ex->getMessage()//'Ocurrió un error al registrar.'
            ], 500);
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.id' => 'required',
            'form.descripcion' => 'required',
            'form.valor' => 'required',
            'form.negrita' => 'required',
            'form.cursiva' => 'required'
        ]);

        try {
            $rTermCond = $request->form;
            $currTermCondition = TermsConditions::where('id', $rTermCond['id']);
            $currTermCondition->update([
                'descripcion' => $rTermCond['descripcion'],
                'valor' => $rTermCond['valor'],
                'negrita' => $rTermCond['negrita'],
                'cursiva' => $rTermCond['cursiva']
            ]);

            return response()
                ->json([
                    'error' => 0,
                    'message' => 'Se ha actualizado correctamente.'
                ], 200);


        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Ocurrió un error al actualizar.'
            ], 500);
        }
    }
}
