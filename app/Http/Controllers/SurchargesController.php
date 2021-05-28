<?php

namespace App\Http\Controllers;

use App\CustomerSurcharges;
use App\RecargoDelivery;
use Carbon\Carbon;
use App\ItemDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SurchargesController extends Controller
{
    public function getSurcharges()
    {
        try {
            $recargos = RecargoDelivery::with(['customer','category','deliveryType'])->get();
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
        $category = $request->form["idCategoria"];
        $deliveryType = $request->form["idTipoEnvio"];
        try {
            $currRate = RecargoDelivery::where('idRecargo', $idSurcharge);
            $currRate->update([
                    'descRecargo' => $desc,
                    'kilomMinimo' => $klmin,
                    'kilomMaximo' => $klmax,
                    'monto' => $monto,
                    'idCategoria' => $category,
                    'idTipoEnvio' => $deliveryType
                ]
            );

            $tK = $request->form['tYK'];
            $vehC = $request->form['cobVehiculo'];
            $dS = $request->form['servChofer'];
            $cR = $request->form['recCombustible'];
            $tCob = $request->form['cobTransporte'];
            $isv = $request->form['isv'];
            $tr = $request->form['tasaTuris'];
            $gastR = $request->form['gastosReembolsables'];

            $currItemDetail = ItemDetail::where('idRecargo', $currRate->get()->first()->idRecargo);

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
                $nID->idTarifaDelivery = $currRate->get()->first()->idRecargo;
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
        $deliveryType = $request->form["idTipoEnvio"];
        $category = $request->form["idCategoria"];
        try {
            $currsurcharge = new RecargoDelivery();
            $currsurcharge->descRecargo = $desc;
            $currsurcharge->kilomMinimo = $klmin;
            $currsurcharge->kilomMaximo = $klmax;
            $currsurcharge->monto = $monto;
            if ($request->form['idCliente'] == 1) {
                $currsurcharge->idCliente = 1;
            }
            $currsurcharge->idTipoEnvio =  $deliveryType;
            $currsurcharge->idCategoria = $category;
            $currsurcharge->save();

            $lastIndex = RecargoDelivery::query()->max('idRecargo');

            if (isset($request->customers)) {
                $customers = $request->customers;
                if (sizeof($customers) > 0) {
                    for ($i = 0; $i < sizeof($customers); $i++) {
                        $exists = CustomerSurcharges::where('idRecargo', $lastIndex)
                            ->where('idCliente',$customers[$i]['idCliente'])
                            ->count();

                        if ($exists == 0) {
                            $nCustSurcharge = new CustomerSurcharges();
                            $nCustSurcharge->idRecargo = $lastIndex;
                            $nCustSurcharge->idCliente = $customers[$i]['idCliente'];
                            $nCustSurcharge->fechaRegistro = Carbon::now();
                            $nCustSurcharge->save();
                        }
                    }
                }
            }

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

    public function addCustomer(Request $request)
    {
        $request->validate([
            'idCliente' => 'required',
            'idRecargo' => 'required'
        ]);

        $customerId = $request->idCliente;
        $surchargeId = $request->idRecargo;
        try {
            $exists = CustomerSurcharges::where('idRecargo', $surchargeId)
                ->where('idCliente', $customerId)
                ->count();

            if ($exists == 0) {
                $nCustSurcharge = new CustomerSurcharges();
                $nCustSurcharge->idRecargo = $surchargeId;
                $nCustSurcharge->idCliente = $customerId;
                $nCustSurcharge->fechaRegistro = Carbon::now();
                $nCustSurcharge->save();

                return response()->json([
                    'error' => 0,
                    'message' => 'El recargo ha sido asignado correctamente'
                ], 200);

            } else {
                return response()->json([
                    'error' => 1,
                    'message' => 'El cliente ya tiene asignado este recargo'
                ], 500);
            }


        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Ha ocurrido un error al agregar el cliente'
            ], 500);
        }
    }

    public function removeCustomer(Request $request)
    {
        $request->validate([
            'idCliente' => 'required',
            'idRecargo' => 'required'
        ]);

        $customerId = $request->idCliente;
        $surchargeId = $request->idRecargo;
        try {
            CustomerSurcharges::where('idRecargo', $surchargeId)
                ->where('idCliente', $customerId)
                ->delete();

            return response()->json([
                'error' => 0,
                'message' => 'Cliente eliminado del recargo correctamente'
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al eliminar el cliente.'
            ], 500);
        }
    }

    public function getCustomers(Request $request)
    {
        $request->validate([
            'idRecargo' => 'required'
        ]);
        $surchargeId = $request->idRecargo;
        try {
            $surchargeCustomers = CustomerSurcharges::with('customer')
                ->where('idRecargo', $surchargeId)
                ->get();

            return response()->json([
                'error' => 0,
                'data' => $surchargeCustomers
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al cargar los clientes del recargo.'
            ], 500);
        }
    }
}
