<?php

namespace App\Http\Controllers;

use App\ConsolidatedRateDetail;
use App\ItemDetail;
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
            $tarifas = Tarifa::with(['category', 'customer', 'rateType', 'consolidatedDetail', 'schedules','itemDetail'])->get();
            foreach ($tarifas as $tarifa) {
                $tarifa->precio = number_format($tarifa->precio, 2);
                foreach ($tarifa->schedules as $schedule) {
                    $schedule->inicio = Carbon::parse($schedule->inicio)->format('H:i');
                    $schedule->final = Carbon::parse($schedule->final)->format('H:i');
                }
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

    public function getCustomerRates(Request $request)
    {
        try {
            if ($request->idCustomer == null) {
                $currCustomer = Auth::user()->idCliente;
            } else {
                $currCustomer = $request->idCustomer;
            }

            $custRates = RateCustomer::where('idCliente', $currCustomer)->get();
            $tarifas = [];

            if ($custRates->count() == 0) {
                $tarifas = Tarifa::where('idCliente', 1)
                    ->where('idTipoTarifa', 1)
                    ->get();
            } else {
                $onlyConsolidated = RateCustomer::where('idCliente', $currCustomer)
                    ->whereHas('rate', function ($q) {
                        $q->whereIn('idTipoTarifa', [2, 4]);
                    })->count();
                if ($onlyConsolidated == $custRates->count()) {
                    $tarifas = Tarifa::where('idCliente', 1)
                        ->where('idTipoTarifa', 1)
                        ->get();
                } else {
                    foreach ($custRates as $value) {
                        $tarifa = Tarifa::where('idTarifaDelivery', $value->idTarifaDelivery)
                            ->where('idTipoTarifa', 1)
                            ->get()
                            ->first();
                        if (!in_array($tarifa, $tarifas) && $tarifa != null) {
                            array_push($tarifas, $tarifa);
                        }

                    }
                }

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

            $tK = $request->form['tYK'] ?? 0;
            $vehC = $request->form['cobVehiculo'] ?? 0;
            $dS = $request->form['servChofer'] ?? 0;
            $cR = $request->form['recCombustible'] ?? 0;
            $tCob = $request->form['cobTransporte'] ?? 0;
            $isv = $request->form['isv'] ?? 0;
            $tr = $request->form['tasaTuris'] ?? 0;
            $gastR = $request->form['gastosReembolsables'] ?? 0;

            $currItemDetail = ItemDetail::where('idTarifaDelivery', $currRate->get()->first()->idTarifaDelivery);

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
                $nID->idTarifaDelivery = $currRate->get()->first()->idTarifaDelivery;
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

                if (isset($request->detail["dirEntrega"])) {
                    $nRateDetail->dirEntrega = $request->detail["dirEntrega"];
                }

                if (isset($request->detail["radioMaximoEntrega"])) {
                    $nRateDetail->radioMaximoEntrega = $request->detail["radioMaximoEntrega"];
                }

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

    public function getSchedules(Request $request)
    {
        $request->validate([
            'idTarifa' => 'required'
        ]);
        $rateId = $request->idTarifa;
        try {
            $rateSchedules = Schedule::where('idTarifaDelivery', $rateId)
                ->get();
            foreach ($rateSchedules as $schedule) {
                $schedule->inicio = Carbon::parse($schedule->inicio)->format('H:i');
                $schedule->final = Carbon::parse($schedule->final)->format('H:i');
            }

            return response()->json([
                'error' => 0,
                'data' => $rateSchedules
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al cargar los clientes de la tarifa.'
            ], 500);
        }
    }

    public function removeSchedule(Request $request)
    {
        $request->validate([
            'idHorario' => 'required',
            'idTarifa' => 'required'
        ]);

        $schdId = $request->idHorario;
        $rateId = $request->idTarifa;
        try {
            Schedule::where('idTarifaDelivery', $rateId)
                ->where('idHorario', $schdId)
                ->delete();

            return response()->json([
                'error' => 0,
                'message' => 'Horario eliminado de la tarifa correctamente'
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al eliminar el horario.'
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

    public function updateRateDetail(Request $request)
    {
        $request->validate([
            'form.idTarifaDelivery' => 'required',
            'form.radioMaximo' => 'required',
            'form.dirRecogida' => 'required'
        ]);

        try {
            $currDetail = ConsolidatedRateDetail::where('idTarifaDelivery', $request->form['idTarifaDelivery']);
            $currDetail->update([
                'radioMaximo' => $request->form['radioMaximo'],
                'dirRecogida' => $request->form['dirRecogida'],
            ]);

            if (isset($request->schedules)) {
                $schedules = $request->schedules;

                foreach ($schedules as $schedule) {

                    $nSch = new Schedule();
                    $nSch->descHorario = $schedule["descHorario"];
                    $nSch->dia = $schedule["dia"];
                    $nSch->cod = $schedule["cod"];
                    $nSch->inicio = date('H:i', strtotime($schedule['inicio']));
                    $nSch->final = date('H:i', strtotime($schedule['final']));
                    $nSch->fechaRegistro = Carbon::now();
                    $nSch->idTarifaDelivery = $request->form['idTarifaDelivery'];
                    $nSch->save();

                }
            }

            return response()->json([
                'error' => 0,
                'message' => 'Detalle de tarifa actualizado correctamente'
            ], 200);

        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), array(
                'User' => Auth::user()->nomUsuario,
                'context' => $ex->getTrace()
            ));
            return response()->json([
                'error' => 1,
                'message' => 'Ha ocurrido un error al actualizar el detalle'
            ], 500);
        }
    }
}
