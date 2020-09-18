<?php

namespace App\Http\Controllers;

use App\DeliveryCustomerWorkLines;
use App\DeliveryWorkLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class WorkLinesController extends Controller
{
    public function list()
    {
        try {
            $workLines = DeliveryWorkLine::where('isActivo', 1)->get();

            return response()->json(
                [
                    'error' => 0,
                    'data' => $workLines
                ],
                200
            );

        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), array(
                'User' => Auth::user()->nomUsuario,
                'context' => $ex->getTrace()
            ));
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al cargar los datos'
                ],
                500
            );
        }
    }

    public function listAll()
    {
        try {
            $workLines = DeliveryWorkLine::all();

            return response()->json(
                [
                    'error' => 0,
                    'data' => $workLines
                ],
                200
            );

        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), array(
                'User' => Auth::user()->nomUsuario,
                'context' => $ex->getTrace()
            ));
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al cargar los datos'
                ],
                500
            );
        }
    }

    public function addCustomer(Request $request)
    {
        $request->validate([
            'customerId' => 'required',
            'worklineId' => 'required'
        ]);

        try {
            $exists = DeliveryCustomerWorkLines::where([
                'idRubro' => $request->worklineId,
                'idCliente' => $request->customerId
            ])->count();
            if ($exists == 0) {
                $nCustomerWL = new DeliveryCustomerWorkLines();
                $nCustomerWL->idRubro = $request->worklineId;
                $nCustomerWL->idCliente = $request->customerId;
                $nCustomerWL->save();
            }

            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Rubro asignado correctamente'
                ],
                200
            );

        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), array(
                'User' => Auth::user()->nomUsuario,
                'context' => $ex->getTrace()
            ));
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al agregar el rubro'
                ],
                500
            );
        }
    }

    public function removeCustomer(Request $request)
    {
        $request->validate([
            'customerId' => 'required',
            'worklineId' => 'required'
        ]);

        try {
            DeliveryCustomerWorkLines::where([
                'idRubro' => $request->worklineId,
                'idCliente' => $request->customerId
            ])->delete();

            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Rubro eliminado correctamente'
                ],
                200
            );

        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), array(
                'User' => Auth::user()->nomUsuario,
                'context' => $ex->getTrace()
            ));
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al eliminar el rubro'
                ],
                500
            );
        }
    }

    public function createWorkLine(Request $request)
    {
        $request->validate(
            [
                'form' => 'required',
                'form.nomRubro' => 'required',
                'form.descRubro' => 'required'
            ]
        );
        $rWL = $request->form;
        try {
            $nWorkLine = new DeliveryWorkLine();
            $nWorkLine->nomRubro = $rWL['nomRubro'];
            $nWorkLine->descRubro = $rWL['descRubro'];
            $nWorkLine->fechaRegistro = Carbon::now();
            $nWorkLine->isActivo = 1;
            $nWorkLine->save();

            return response()->json([
                'error' => 0,
                'message' => 'Rubro agregado correctamente.'
            ], 200);

        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al agregar el rubro.'
            ], 500);
        }
    }

    public function updateWorkLine(Request $request)
    {
        $request->validate(
            [
                'form' => 'required',
                'form.idRubro' => 'required',
                'form.nomRubro' => 'required',
                'form.descRubro' => 'required'
            ]
        );
        $idWL = $request->form["idRubro"];
        
        try {
            $currWL = DeliveryWorkLine::where('idRubro', $idWL);
            $currWL->update([
                'nomRubro' => $request->form["nomRubro"],
                'descRubro' => $request->form["descRubro"],
                'isActivo' => $request->form['isActivo']
            ]);

            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Rubro actualizado correctamente.'
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Error al actualizar el rubro.'
                ],
                500
            );
        }
    }
}
