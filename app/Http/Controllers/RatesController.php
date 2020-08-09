<?php

namespace App\Http\Controllers;

use App\ConsolidatedRateDetail;
use App\RateCustomer;
use App\Schedule;
use App\Tarifa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RatesController extends Controller
{
    public function getRates()
    {
        try {
            $tarifas = Tarifa::with(['category', 'customer', 'rateType', 'consolidatedDetail', 'schedules'])->get();
            foreach ($tarifas as $tarifa) {
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
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ha ocurrido un error al cargar los datos'
                ],
                500
            );
        }
    }

    public function getCustomerRates()
    {
        try {
            $currCustomer = Auth::user()->idCliente;
            $custRates = RateCustomer::where('idCliente', $currCustomer)->get();
            $tarifas = [];
            $tarifasConsolidadas = [];

            if ($custRates->count() == 0) {
                $tarifas = Tarifa::where('idCliente', 1)->where('idTipoTarifa', 1)->get();
            } else {
                foreach ($custRates as $value) {
                    $tarifa = Tarifa::where('idTarifaDelivery', $value->idTarifaDelivery)->where('idTipoTarifa', 1)->get()->first();
                    array_push($tarifas, $tarifa);

                    $tarifasCons = Tarifa::with(['consolidatedDetail', 'schedules'])
                        ->where('idTarifaDelivery', $value->idTarifaDelivery)
                        ->where('idTipoTarifa', 2)->get()->first();

                    array_push($tarifasConsolidadas, $tarifasCons);
                }
            }


            return response()->json(
                [
                    'error' => 0,
                    'data' => $tarifas,
                    'consolidadas' => $tarifasConsolidadas
                ],
                200
            );
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ha ocurrido un error al cargar los datos'
                ],
                500
            );
        }
    }

    public function updateRate(Request $request)
    {
        $request->validate([
            'form.idTarifaDelivery' => 'required',
            'form.descTarifa' => 'required',
            'form.idCategoria' => 'required',
            'form.entregasMinimas' => 'required',
            'form.entregasMaximas' => 'required',
            'form.precio' => 'required',
            'form.idTipoTarifa' => 'required',
        ]);
        $idRate = $request->form["idTarifaDelivery"];
        $descRate = $request->form["descTarifa"];
        $idCategoria = $request->form["idCategoria"];
        $emin = $request->form["entregasMinimas"];
        $emax = $request->form["entregasMaximas"];
        $monto = $request->form["precio"];
        $rateType = $request->form["idTipoTarifa"];

        try {
            $currRate = Tarifa::where('idTarifaDelivery', $idRate);
            $currRate->update([
                    'idCategoria' => $idCategoria,
                    'descTarifa' => $descRate,
                    'entregasMinimas' => $emin,
                    'entregasMaximas' => $emax,
                    'precio' => $monto,
                    'idTipoTarifa' => $rateType
                ]
            );

            return response()->json([
                'error' => 0,
                'message' => 'Tarifa actualizada correctamente.'
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al actualizar la tarifa.'
            ], 500);
        }
    }

    public function createRate(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.descTarifa' => 'required',
            'form.idCategoria' => 'required',
            'form.entregasMinimas' => 'required',
            'form.entregasMaximas' => 'required',
            'form.idTipoTarifa' => 'required',
            'form.precio' => 'required'
        ]);

        $descRate = $request->form["descTarifa"];
        $idCategoria = $request->form["idCategoria"];
        $emin = $request->form["entregasMinimas"];
        $emax = $request->form["entregasMaximas"];
        $monto = $request->form["precio"];
        $rateType = $request->form["idTipoTarifa"];

        try {
            $currRate = new Tarifa();
            $currRate->descTarifa = $descRate;
            $currRate->idCategoria = $idCategoria;
            $currRate->entregasMinimas = $emin;
            $currRate->entregasMaximas = $emax;
            $currRate->precio = $monto;
            $currRate->idTipoTarifa = $rateType;
            $currRate->fechaRegistro = Carbon::now();
            if ($request->form['idCliente'] == 1) {
                $currRate->idCliente = 1;
            }
            $currRate->save();

            $lastIndex = Tarifa::query()->max('idTarifaDelivery');

            if (isset($request->customers)) {
                $customers = $request->customers;
                if (sizeof($customers) > 0) {
                    for ($i = 0; $i < sizeof($customers); $i++) {
                        $existe = RateCustomer::where('idTarifaDelivery', $lastIndex)
                            ->where('idCliente', $customers[$i]['idCliente'])->count();
                        if ($existe == 0) {
                            $nCustRate = new RateCustomer();
                            $nCustRate->idTarifaDelivery = $lastIndex;
                            $nCustRate->idCliente = $customers[$i]['idCliente'];
                            $nCustRate->fechaRegistro = Carbon::now();
                            $nCustRate->save();
                        }
                    }

                }
            }

            if (isset($request->detail)) {
                $maxRad = $request->detail["radioMaximo"];
                $addrs = $request->detail["dirRecogida"];
                $rateSchedules = $request->schedules;

                $nRateDetail = new ConsolidatedRateDetail();
                $nRateDetail->idTarifaDelivery = $lastIndex;
                $nRateDetail->radioMaximo = $maxRad;
                $nRateDetail->dirRecogida = $addrs;
                $nRateDetail->save();

                foreach ($rateSchedules as $schedule) {
                    $nSch = new Schedule();
                    $nSch->descHorario = $schedule["descHorario"];
                    $nSch->dia = $schedule["dia"];
                    $nSch->cod = $schedule["cod"];
                    $nSch->inicio = date('H:i', strtotime($schedule['inicio']));
                    $nSch->final = date('H:i', strtotime($schedule['final']));
                    $nSch->fechaRegistro = Carbon::now();
                    $nSch->idTarifaDelivery = $lastIndex;
                    $nSch->save();
                }
            }

            return response()->json([
                'error' => 0,
                'message' => 'Tarifa agregada correctamente.'
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Ha ocurrido un error al cargar los datos'
            ], 500);
        }
    }

    public function getCustomers(Request $request)
    {
        $request->validate([
            'idTarifa' => 'required'
        ]);
        $rateId = $request->idTarifa;
        try {
            $rateCustomers = RateCustomer::with('customer')
                ->where('idTarifaDelivery', $rateId)
                ->get();

            return response()->json([
                'error' => 0,
                'data' => $rateCustomers
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al cargar los clientes de la tarifa.'
            ], 500);
        }
    }

    public function removeCustomer(Request $request)
    {
        $request->validate([
            'idCliente' => 'required',
            'idTarifa' => 'required'
        ]);

        $customerId = $request->idCliente;
        $rateId = $request->idTarifa;
        try {
            RateCustomer::where('idTarifaDelivery', $rateId)
                ->where('idCliente', $customerId)->delete();

            return response()->json([
                'error' => 0,
                'message' => 'Cliente eliminado de la tarifa correctamente'
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al eliminar el cliente.'
            ], 500);
        }
    }

    public function addCustomer(Request $request)
    {
        $request->validate([
            'idCliente' => 'required',
            'idTarifa' => 'required'
        ]);

        $customerId = $request->idCliente;
        $rateId = $request->idTarifa;
        try {
            $existe = RateCustomer::where('idTarifaDelivery', $rateId)
                ->where('idCliente', $customerId)->count();

            if ($existe == 0) {
                $nCustRate = new RateCustomer();
                $nCustRate->idTarifaDelivery = $rateId;
                $nCustRate->idCliente = $customerId;
                $nCustRate->fechaRegistro = Carbon::now();
                $nCustRate->save();

                return response()->json([
                    'error' => 0,
                    'message' => 'La tarifa ha sido asignada correctamente'
                ], 200);

            } else {
                return response()->json([
                    'error' => 1,
                    'message' => 'El cliente ya tiene asignada esta tarifa'
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
}
