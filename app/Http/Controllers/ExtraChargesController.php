<?php

namespace App\Http\Controllers;

use App\DetalleOpcionesCargosExtras;
use App\ExtraCharge;
use App\ExtraChargeCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\ItemDetail;

class ExtraChargesController extends Controller
{
    public function get()
    {
        try {
            $extraCharges = ExtraCharge::with(['options','itemDetail'])->get();

            return response()
                ->json(
                    [
                        'error' => 0,
                        'data' => $extraCharges
                    ],
                    200
                );
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), array(
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

    public function getExtraChargeCategories(Request $request)
    {
        $request->validate([
            'idCargoExtra' => 'required'
        ]);

        $extraChargeId = $request->idCargoExtra;
        try {
            $extraChargeCategories = ExtraChargeCategory::with('category')
                ->where('idCargoExtra', $extraChargeId)
                ->get();

            return response()->json([
                'error' => 0,
                'data' => $extraChargeCategories
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al cargar las categorías del cargo extra.'
            ], 500);
        }
    }

    public function create(Request $request)
    {
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
                ->json(
                    [
                        'error' => 0,
                        'message' => 'Cargo extra agregado correctamente.'
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
                    'message' => 'Ocurrió un error al agregar el cargo extra'
                ],
                500
            );
        }
    }

    public function removeCategory(Request $request)
    {
        $request->validate([
            'idCargoExtra' => 'required',
            'idCategoria' => 'required'
        ]);

        $categoryId = $request->idCategoria;
        $extraChargeId = $request->idCargoExtra;

        try {
            ExtraChargeCategory::where('idCargoExtra', $extraChargeId)
                ->where('idCategoria', $categoryId)->delete();

            return response()->json([
                'error' => 0,
                'message' => 'Categoría eliminada del cargo extra correctamente'
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al eliminar la categoría'
            ], 500);
        }
    }

    public function addCategory(Request $request)
    {
        $request->validate([
            'idCargoExtra' => 'required',
            'idCategoria' => 'required'
        ]);

        $categoryId = $request->idCategoria;
        $extraChargeId = $request->idCargoExtra;

        try {
            $existe = ExtraChargeCategory::where('idCargoExtra', $extraChargeId)
                ->where('idCategoria', $categoryId)->count();
            if ($existe == 0) {
                $nCatEC = new ExtraChargeCategory();
                $nCatEC->idCargoExtra = $extraChargeId;
                $nCatEC->idCategoria = $categoryId;
                $nCatEC->fechaRegistro = Carbon::now();
                $nCatEC->save();

                return response()->json([
                    'error' => 0,
                    'message' => 'La categoría ha sido asignada correctamente'
                ], 200);
            } else {
                return response()->json([
                    'error' => 1,
                    'message' => 'Este cargo extra ya tiene asignado ésta categoría'
                ], 500);
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al asignar la categoría'
            ], 500);
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'ecId' => 'required',
            'form' => 'required',
            'form.nombre' => 'required',
            'form.costo' => 'required',
            'form.tipoCargo' => 'required'

        ]);

        try {
            $rNom = $request->form['nombre'];
            $rCost = $request->form['costo'];
            $rTypeEC = $request->form['tipoCargo'];
            $aEcId = $request->ecId;

            $aEC = ExtraCharge::where('idCargoExtra', $aEcId);

            $aEC->update([
                'nombre' => $rNom,
                'costo' => $rCost,
                'tipoCargo' => $rTypeEC
            ]);

            $tK = $request->form['tYK'] ?? 0;
            $vehC = $request->form['cobVehiculo'] ?? 0;
            $dS = $request->form['servChofer'] ?? 0;
            $cR = $request->form['recCombustible'] ?? 0;
            $tCob = $request->form['cobTransporte'] ?? 0;
            $isv = $request->form['isv'] ?? 0;
            $tr = $request->form['tasaTuris'] ?? 0;
            $gastR = $request->form['gastosReembolsables'] ?? 0;

            $currItemDetail = ItemDetail::where('idCargoExtra', $aEC->get()->first()->idCargoExtra);

            if ($currItemDetail->count() > 0) {
                $currItemDetail->update([
                    'tYK' => $tK,
                    'cobVehiculo' => $vehC,
                    'servChofer' => $dS,
                    'recCombustible' => $cR,
                    'cobTransporte' => $tCob,
                    'isv' => $isv,
                    'tasaTuris' => $tr,
                    'gastosReembolsables' => $gastR
                ]);
            } else {
                $nID = new ItemDetail();
                $nID->idCargoExtra = $aEC->get()->first()->idCargoExtra;
                $nID->tYK = $tK;
                $nID->cobVehiculo = $vehC;
                $nID->servChofer = $dS;
                $nID->recCombustible = $cR;
                $nID->cobTransporte = $tCob;
                $nID->isv = $isv;
                $nID->tasaTuris = $tr;
                $nID->gastosReembolsables = $gastR;
                $nID->save();
            }

            return response()
                ->json(
                    [
                        'error' => 0,
                        'message' => 'Cargo extra actualizado correctamente.'
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
                    'message' => 'Ocurrió un error al actualizar el cargo extra'
                ],
                500
            );
        }
    }

    public function getExtraChargeOptions(Request $request)
    {
        $request->validate(['idCargoExtra' => 'required']);
        $extrachargeId = $request->idCargoExtra;
        try {
            $options = DetalleOpcionesCargosExtras::with('itemDetail')->where('idCargoExtra', $extrachargeId)->get();
            foreach ($options as $option) {
                $option->costo = number_format($option->costo, 2);
            }

            return response()->json([
                'error' => 0,
                'data' => $options
            ], 200);
        } catch (\Exception $exception) {
            Log::error(
                $exception->getMessage(),
                array('User' => Auth::user()->nomUsuario, 'context' => $exception->getTrace())
            );
            return response()
                ->json([
                    'error' => 1,
                    'message' => 'Ocurrió un error al cargar los datos'
                ], 500);
        }
    }

    public function addOption(Request $request)
    {
        $request->validate([
            'idCargoExtra' => 'required',
            'form' => 'required',
            'form.descripcion' => 'required',
            'form.tiempo' => 'required',
            'form.costo' => 'required'
        ]);

        $extraChargeId = $request->idCargoExtra;
        $optionDesc = $request->form['descripcion'];
        $optionCost = $request->form['costo'];
        $optionTime = $request->form['tiempo'];

        try {

            $nECOpt = new DetalleOpcionesCargosExtras();
            $nECOpt->idCargoExtra = $extraChargeId;
            $nECOpt->descripcion = $optionDesc;
            $nECOpt->costo = $optionCost;
            $nECOpt->tiempo = $optionTime;
            $nECOpt->save();

            return response()->json([
                'error' => 0,
                'message' => 'La opción ha sido asignada correctamente'
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al asignar la opción'
            ], 500);
        }
    }

    public function removeOption(Request $request)
    {
        $request->validate([
            'idCargoExtra' => 'required',
            'optionId' => 'required'
        ]);

        $optionId = $request->optionId;
        $extraChargeId = $request->idCargoExtra;

        try {
            DetalleOpcionesCargosExtras::where('idCargoExtra', $extraChargeId)
                ->where('idDetalleOpcion', $optionId)->delete();

            return response()->json([
                'error' => 0,
                'message' => 'Opción eliminada del cargo extra correctamente'
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al eliminar la Opción'
            ], 500);
        }
    }

    public function editOption(Request $request)
    {
        $request->validate([
            'idCargoExtra' => 'required',
            'idDetalleOpcion' => 'required',
            'form.costo' => 'required',
            'form.tiempo' => 'required'
        ]);

        $optionId = $request->idDetalleOpcion;
        $extraChargeId = $request->idCargoExtra;

        try {
            $currOption = DetalleOpcionesCargosExtras::where([
                'idCargoExtra' => $extraChargeId,
                'idDetalleOpcion' => $optionId
            ]);

            $currOption ->update([
                    'costo' => $request->form['costo'],
                    'tiempo' => $request->form['tiempo']
                ]);

            $tK = $request->form['tYK'] ?? 0;
            $vehC = $request->form['cobVehiculo'] ?? 0;
            $dS = $request->form['servChofer'] ?? 0;
            $cR = $request->form['recCombustible'] ?? 0;
            $tCob = $request->form['cobTransporte'] ?? 0;
            $isv = $request->form['isv'] ?? 0;
            $tr = $request->form['tasaTuris'] ?? 0;
            $gastR = $request->form['gastosReembolsables'] ?? 0;

            $currItemDetail = ItemDetail::where('idDetalleOpcion', $currOption->get()->first()->idDetalleOpcion);

            if ($currItemDetail->count() > 0) {
                $currItemDetail->update([
                    'tYK' => $tK,
                    'cobVehiculo' => $vehC,
                    'servChofer' => $dS,
                    'recCombustible' => $cR,
                    'cobTransporte' => $tCob,
                    'isv' => $isv,
                    'tasaTuris' => $tr,
                    'gastosReembolsables' => $gastR
                ]);
            } else {
                $nID = new ItemDetail();
                $nID->idDetalleOpcion = $currOption->get()->first()->idDetalleOpcion;
                $nID->tYK = $tK;
                $nID->cobVehiculo = $vehC;
                $nID->servChofer = $dS;
                $nID->recCombustible = $cR;
                $nID->cobTransporte = $tCob;
                $nID->isv = $isv;
                $nID->tasaTuris = $tr;
                $nID->gastosReembolsables = $gastR;
                $nID->save();
            }

            return response()->json([
                'error' => 0,
                'message' => 'Opción actualizada correctamente'
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al actualizar la Opción'
            ], 500);
        }
    }

    public function getFilteredExtraCharges(Request $request)
    {
        $request->validate(['idCategoria' => 'required']);
        $category = $request->idCategoria;
        try {
            $extraCharges = ExtraCharge::with(['options'])
                ->whereHas('extrachargeCategories', function ($q) use ($category) {
                    $q->where('idCategoria', $category);
                })
                ->get();

            return response()
                ->json(
                    [
                        'error' => 0,
                        'data' => $extraCharges
                    ],
                    200
                );
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al cargar los datos'
            ], 500);
        }
    }
}
