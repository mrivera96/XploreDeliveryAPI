<?php

namespace App\Http\Controllers;

use App\Category;
use App\CtrlEstadoDelivery;
use App\Delivery;
use App\DeliveryClient;
use App\DetalleDelivery;
use App\DetalleOpcionesCargosExtras;
use App\Estado;
use App\ExtraCharge;
use App\ExtraChargesOrders;
use App\ItemDetail;
use App\OrderExtrachargeFactValues;
use App\OrderFactValues;
use App\OrderSurchargeFactValues;
use App\Schedule;
use App\User;
use App\DeliveryTransaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DeliveriesController extends Controller
{

    public function resendMail(Request $request)
    {
        $request->validate([
            'mail' => 'required',
            'deliveryId' => 'required'
        ]);

        $this->sendmail($request->mail, $request->deliveryId);
    }

    /*********************************
     * FUNCIONES COMPARTIDAS
     ********************************/

    public function getById(Request $request)
    {
        try {
            if (Auth::user()->idPerfil == 1 || Auth::user()->idPerfil == 9) {
                $delivery = Delivery::with(['usuario', 'estado', 'detalle.conductor', 'detalle.auxiliar',
                    'detalle.estado', 'detalle.photography', 'detalle.delivery',
                    'detalle.extraCharges.extracharge', 'detalle.extraCharges.option'])
                    ->where('idDelivery', $request->id)
                    ->with(['category.surcharges', 'detalle'])
                    ->get()->first();

                /*$sameAddressDeliveries = Delivery::with(['usuario', 'estado', 'detalle.conductor', 'detalle.auxiliar',
                    'detalle.estado', 'detalle.photography', 'detalle.delivery',
                    'detalle.extraCharges.extracharge', 'detalle.extraCharges.option'])
                    ->where('dirRecogida', $delivery->dirRecogida)
                    ->whereBetween('fechaReserva',[date('Y-m-d', strtotime($delivery->fechaReserva. '- 7 days')).' 00:00:00', date('Y-m-d', strtotime($delivery->fechaReserva)).' 23:59:59'])
                    ->orWhereBetween('fechaReserva',[date('Y-m-d', strtotime($delivery->fechaReserva)).' 00:00:00', date('Y-m-d', strtotime($delivery->fechaReserva. '+ 7 days')).' 23:59:59'])
                    ->where('dirRecogida', $delivery->dirRecogida)
                    ->with(['category.surcharges', 'detalle'])
                    ->get();*/
            } else {
                $delivery = Delivery::with(['estado', 'detalle.conductor', 'detalle.extraCharges', 'detalle.estado', 'detalle.photography', 'category'])
                    ->where('idCliente', Auth::user()->idCliente)->where('idDelivery', $request->id)
                    ->get()->first();
            }

            $delivery->fechaNoFormatted = $delivery->fechaReserva;
            $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('d/m/Y, h:i a');
            $delivery->fechaRegistro = \Carbon\Carbon::parse($delivery->fechaRegistro)->format('d/m/Y, h:i a');
            $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
            $delivery->recargos = number_format($delivery->recargos, 2);
            $delivery->cargosExtra = number_format($delivery->cargosExtra, 2);
            $delivery->total = number_format($delivery->total, 2);
            foreach ($delivery->detalle as $detail) {
                $detail->tarifaBase = number_format($detail->tarifaBase, 2);
                $detail->recargo = number_format($detail->recargo, 2);
                $detail->cargosExtra = number_format($detail->cargosExtra, 2);
                $detail->cTotal = number_format($detail->cTotal, 2);
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => $delivery,
                    /*'sameAddress' => $sameAddressDeliveries ?? null,
                    'initDate' => new Carbon(date('Y-m-d', strtotime($delivery->fechaNoFormatted. '- 7 days')).' 00:00:00'),//new Carbon(date('Y-m-d', strtotime($delivery->fechaNoFormatted)). 'T00:00:00'),
                    'finDate' => new Carbon(date('Y-m-d', strtotime($delivery->fechaNoFormatted)).' 23:59:59')//new Carbon(date('Y-m-d', strtotime($delivery->fechaNoFormatted. '- 7 days')). 'T23:59:59')*/
                ],
                200
            );
        } catch (Exception $ex) {
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

    /*********************************
     * FUNCIONES DE ADMINISTRADORES
     ********************************/
    public function getTodayDeliveries()
    {
        try {
            $deliveriesDia = Delivery::whereDate('fechaReserva', Carbon::today())
                ->with(['category', 'detalle', 'estado'])->get();

            foreach ($deliveriesDia as $delivery) {
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d H:i');
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->cargosExtra = number_format($delivery->cargosExtra, 2);
                $delivery->total = number_format($delivery->total, 2);
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => $deliveriesDia
                ],
                200
            );
        } catch (Exception $ex) {
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

    public function getTomorrowDeliveries()
    {
        try {
            $deliveriesTomorrow = Delivery::whereDate('fechaReserva', Carbon::tomorrow())
                ->with(['category', 'detalle', 'estado'])->get();

            foreach ($deliveriesTomorrow as $delivery) {
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d H:i');
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->cargosExtra = number_format($delivery->cargosExtra, 2);
                $delivery->total = number_format($delivery->total, 2);
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => $deliveriesTomorrow
                ],
                200
            );
        } catch (Exception $ex) {
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

    public function getAllDeliveries()
    {
        try {

            $allDeliveries = Delivery::with(['category', 'detalle', 'estado'])
                ->whereBetween(
                    'fechaReserva',
                    [
                        Carbon::now()->subDays(7),
                        Carbon::now()
                    ]
                )
                ->get();

            foreach ($allDeliveries as $delivery) {
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d H:i');
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->cargosExtra = number_format($delivery->cargosExtra, 2);
                $delivery->total = number_format($delivery->total, 2);
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => $allDeliveries
                ],
                200
            );
        } catch (Exception $ex) {
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

    public function getFilteredDeliveries(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.initDate' => 'required',
            'form.finDate' => 'required'
        ]);

        try {
            if (Auth::user()->idPerfil == 8) {
                $deliveries = Delivery::with(['category', 'detalle', 'estado'])
                    ->where(['idCliente' => Auth::user()->idCliente])
                    ->whereBetween('fechaReserva', [
                        $request->form['initDate'] . ' 00:00:00',
                        $request->form['finDate'] . ' 23:59:59'
                    ])
                    ->get();
            } else {
                $deliveries = Delivery::with(['category', 'detalle', 'estado'])
                    ->whereBetween('fechaReserva', [
                        $request->form['initDate'] . ' 00:00:00',
                        $request->form['finDate'] . ' 23:59:59'
                    ])
                    ->get();
            }

            foreach ($deliveries as $delivery) {
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d H:i');
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->cargosExtra = number_format($delivery->cargosExtra, 2);
                $delivery->total = number_format($delivery->total, 2);
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => $deliveries
                ],
                200
            );
        } catch (Exception $ex) {
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

    public function getPendingDeliveries()
    {
        try {
            $pendingDeliveries = DB::select('EXEC [Delivery].[ListadoEntregasPorAsignar]');
            foreach ($pendingDeliveries as $delivery) {
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d H:i');
            }
            return response()->json(
                [
                    'error' => 0,
                    'data' => $pendingDeliveries
                ],
                500
            );
        } catch (Exception $ex) {
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

    public function getCustomerPendingDeliveries()
    {
        try {
            $user = Auth::user();

            $allDeliveries = Delivery::where('idCliente', $user->idCliente)
                ->whereHas('detalle', function ($q) {
                    $q->whereNotIn('idEstado', [41, 42, 43, 44]);
                })
                ->whereIn('idEstado', [34, 37, 38])
                ->with(['category', 'detalle', 'estado'])->get();

            foreach ($allDeliveries as $delivery) {
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d H:i');
                //$delivery->fechaReserva = date('d-m-Y h:i', strtotime($delivery->fechaReserva));
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->cargosExtra = number_format($delivery->cargosExtra, 2);
                $delivery->total = number_format($delivery->total, 2);
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => $allDeliveries
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al cargar los datos'
                ],
                500
            );
        }
    }

    public function getTodayOrders()
    {
        try {
            $deliveriesDia = DetalleDelivery::with([
                'delivery.category.surcharges', 'estado', 'conductor', 'photography',
                'extraCharges.extracharge', 'extraCharges.option'
            ])
                ->whereHas('delivery', function ($q) {
                    $q->whereDate('fechaReserva', Carbon::today());
                })->get();
            $pedidosDia = [];

            foreach ($deliveriesDia as $dtl) {
                $dtl->fechaEntrega = \Carbon\Carbon::parse($dtl->fechaEntrega)->format('Y-m-d H:i');
                $dtl->tarifaBase = number_format($dtl->tarifaBase, 2);
                $dtl->recargo = number_format($dtl->recargo, 2);
                $dtl->cargosExtra = number_format($dtl->cargosExtra, 2);
                $dtl->cTotal = number_format($dtl->cTotal, 2);
                array_push($pedidosDia, $dtl);
            }


            return response()->json(
                [
                    'error' => 0,
                    'data' => $pedidosDia
                ],
                200
            );
        } catch (Exception $ex) {
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

    public function getAllOrders()
    {
        try {
            $allDeliveries = DetalleDelivery::with([
                'delivery.category.surcharges', 'estado', 'conductor', 'auxiliar',
                'photography', 'extraCharges.extracharge', 'extraCharges.option'
            ])
                ->whereHas('delivery', function ($q) {
                    $q->whereBetween('fechaReserva', [
                        Carbon::now()->subDays(7),
                        Carbon::now()
                    ]);
                })
                ->get();
            $todosPedidos = [];

            foreach ($allDeliveries as $dtl) {
                $dtl->fechaEntrega = \Carbon\Carbon::parse($dtl->fechaEntrega)->format('Y-m-d H:i');
                $dtl->tarifaBase = number_format($dtl->tarifaBase, 2);
                $dtl->recargo = number_format($dtl->recargo, 2);
                $dtl->cargosExtra = number_format($dtl->cargosExtra, 2);
                $dtl->cTotal = number_format($dtl->cTotal, 2);
                array_push($todosPedidos, $dtl);
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => $todosPedidos
                ],
                200
            );
        } catch (Exception $ex) {
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

    public function getFilteredOrders(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.initDate' => 'required',
            'form.finDate' => 'required'
        ]);

        try {
            if (Auth::user()->idPerfil == 8) {
                $orders = DetalleDelivery::with([
                    'delivery.category.surcharges', 'estado', 'conductor', 'photography', 'auxiliar',
                    'extraCharges.extracharge', 'extraCharges.option'
                ])
                    ->whereHas('delivery', function ($q) use ($request) {
                        $q->where('idCliente', Auth::user()->idCliente)
                            ->whereBetween('fechaReserva', [
                                $request->form['initDate'] . ' 00:00:00',
                                $request->form['finDate'] . ' 23:59:59'
                            ]);
                    })
                    ->get();
            } else {
                $orders = DetalleDelivery::with([
                    'delivery.category.surcharges', 'estado', 'conductor', 'photography', 'auxiliar',
                    'extraCharges.extracharge', 'extraCharges.option'
                ])
                    ->whereHas('delivery', function ($q) use ($request) {
                        $q->whereBetween('fechaReserva', [
                            $request->form['initDate'] . ' 00:00:00',
                            $request->form['finDate'] . ' 23:59:59'
                        ]);
                    })
                    ->get();
            }

            $todosPedidos = [];

            foreach ($orders as $dtl) {
                $dtl->fechaEntrega = \Carbon\Carbon::parse($dtl->fechaEntrega)->format('Y-m-d H:i');
                $dtl->tarifaBase = number_format($dtl->tarifaBase, 2);
                $dtl->recargo = number_format($dtl->recargo, 2);
                $dtl->cargosExtra = number_format($dtl->cargosExtra, 2);
                $dtl->cTotal = number_format($dtl->cTotal, 2);
                array_push($todosPedidos, $dtl);
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => $todosPedidos
                ],
                200
            );
        } catch (Exception $ex) {
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

    //Report Orders By Driver

    public function reportOrdersByDriver(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.driverId' => 'required',
            'form.initDate' => 'required',
            'form.finDate' => 'required'
        ]);

        $form = $request->form;
        $driver = $form['driverId'];

        $initDateTime = new Carbon(date('Y-m-d', strtotime($form['initDate'])) . ' 00:00:00');
        $finDateTime = new Carbon(date('Y-m-d', strtotime($form['finDate'])) . ' 23:59:59');

        try {
            $outputData = [];

            if ($driver == -1) {

                $drivers = User::where(['isActivo' => 1, 'idPerfil' => 7])
                    ->orderBy('nomUsuario', 'ASC')
                    ->get(['nomUsuario', 'idUsuario']);

                foreach ($drivers as $driver) {
                    $orders = DetalleDelivery::with(['delivery'])
                        ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                        ->whereIn('idEstado', [44, 46, 47])
                        ->where('idConductor', $driver->idUsuario)
                        ->orWhere('idAuxiliar', $driver->idUsuario)
                        ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                        ->whereIn('idEstado', [44, 46, 47])
                        ->orderBy('fechaEntrega', 'desc')
                        ->get()
                        ->groupBy(function ($val) {
                            return Carbon::parse($val->fechaEntrega)->format('Y-m-d');
                        });

                    foreach ($orders as $key => $order) {
                        $dataObj = (object)array();
                        $dataObj->driver = $driver->nomUsuario;
                        $dataObj->fecha = $key;
                        $dataObj->moto = 0;
                        $dataObj->turismo = 0;
                        $dataObj->pickup = 0;
                        $dataObj->panel = 0;
                        $dataObj->pickupAuxiliar = 0;
                        $dataObj->panelAuxiliar = 0;
                        $dataObj->transTurism = 0;
                        $dataObj->camion11 = 0;
                        $dataObj->nextDay = 0;
                        $dataObj->motoTime = 0;
                        $dataObj->motoMoney = 0;
                        $dataObj->motoOver20kms = 0;
                        $dataObj->turismoTime = 0;
                        $dataObj->turismoMoney = 0;
                        $dataObj->turismoOver20kms = 0;
                        $dataObj->pickupTime = 0;
                        $dataObj->pickupMoney = 0;
                        $dataObj->pickupOver20kms = 0;
                        $dataObj->panelTime = 0;
                        $dataObj->panelMoney = 0;
                        $dataObj->panelOver20kms = 0;
                        $dataObj->pickupAuxiliarTime = 0;
                        $dataObj->pickupAuxiliarMoney = 0;
                        $dataObj->pickupAuxiliarOver20kms = 0;
                        $dataObj->panelAuxiliarTime = 0;
                        $dataObj->panelAuxiliarMoney = 0;
                        $dataObj->panelAuxiliarOver20kms = 0;
                        $dataObj->transTurismTime = 0;
                        $dataObj->transTurismMoney = 0;
                        $dataObj->transTurismOver20kms = 0;
                        $dataObj->camion11Time = 0;
                        $dataObj->camion11Money = 0;
                        $dataObj->camion11Over20kms = 0;
                        $dataObj->nextDayTime = 0;
                        $dataObj->nextDayMoney = 0;
                        $dataObj->nextDayOver20kms = 0;

                        for ($i = 0; $i < sizeof($order); $i++) {
                            $tCounterMoto = 0;
                            $mCounterMoto = 0;
                            $o20CounterMoto = 0;
                            $tCounterTurismo = 0;
                            $mCounterTurismo = 0;
                            $o20CounterTurismo = 0;
                            $tCounterPickup = 0;
                            $mCounterPickup = 0;
                            $o20CounterPickup = 0;
                            $tCounterPanel = 0;
                            $mCounterPanel = 0;
                            $o20CounterPanel = 0;
                            $tCounterPickupAuxiliar = 0;
                            $mCounterPickupAuxiliar = 0;
                            $o20CounterPickupAuxiliar = 0;
                            $tCounterPanelAuxiliar = 0;
                            $mCounterPanelAuxiliar = 0;
                            $o20CounterPanelAuxiliar = 0;
                            $tCounterTransTurism = 0;
                            $mCounterTransTurism = 0;
                            $o20CounterTransTurism = 0;
                            $tCounterCamion11 = 0;
                            $mCounterCamion11 = 0;
                            $o20CounterCamion11 = 0;
                            $tCounternextDay = 0;
                            $mCounternextDay = 0;
                            $o20CounternextDay = 0;

                            switch ($order[$i]->delivery->idCategoria) {
                                case 6:
                                    $dataObj->moto++;

                                    if ($order[$i]->tiempo != null) {
                                        if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                            $stime = explode(' ', $order[$i]->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterMoto = $o20CounterMoto + intval($time);
                                            }
                                            $order[$i]->tiempo = 20 + intval($time);
                                            $tCounterMoto = $tCounterMoto + intval($order[$i]->tiempo);
                                        } else {
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterMoto = $o20CounterMoto + intval($order[$i]->tiempo);
                                            }
                                            $order[$i]->tiempo = 20 + intval($order[$i]->tiempo);
                                            $tCounterMoto = $tCounterMoto + intval($order[$i]->tiempo);
                                        }
                                    }
                                    $mCounterMoto = $mCounterMoto + $order[$i]->efectivoRecibido;

                                    $dataObj->motoTime += $tCounterMoto;
                                    $dataObj->motoMoney += $mCounterMoto;
                                    $dataObj->motoOver20kms += $o20CounterMoto;
                                    break;
                                case 1:
                                    $dataObj->turismo++;

                                    if ($order[$i]->tiempo != null) {
                                        if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                            $stime = explode(" ", $order[$i]->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterTurismo = $o20CounterTurismo + intval($time);
                                            }
                                            $order[$i]->tiempo = 20 + intval($time);
                                            $tCounterTurismo = $tCounterTurismo + intval($order[$i]->tiempo);
                                        } else {
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterTurismo = $o20CounterTurismo + intval($order[$i]->tiempo);
                                            }
                                            $order[$i]->tiempo = 20 + intval($order[$i]->tiempo);
                                            $tCounterTurismo = $tCounterTurismo + intval($order[$i]->tiempo);
                                        }
                                    }
                                    $mCounterTurismo = $mCounterTurismo + $order[$i]->efectivoRecibido;

                                    $dataObj->turismoTime += $tCounterTurismo;
                                    $dataObj->turismoMoney += $mCounterTurismo;
                                    $dataObj->turismoOver20kms += $o20CounterTurismo;
                                    break;

                                case 13:
                                    $dataObj->turismo++;

                                    if ($order[$i]->tiempo != null) {
                                        if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                            $stime = explode(" ", $order[$i]->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterTurismo = $o20CounterTurismo + intval($time);
                                            }
                                            $order[$i]->tiempo = 20 + intval($time);
                                            $tCounterTurismo = $tCounterTurismo + intval($order[$i]->tiempo);
                                        } else {
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterTurismo = $o20CounterTurismo + intval($order[$i]->tiempo);
                                            }
                                            $order[$i]->tiempo = 20 + intval($order[$i]->tiempo);
                                            $tCounterTurismo = $tCounterTurismo + intval($order[$i]->tiempo);
                                        }
                                    }
                                    $mCounterTurismo = $mCounterTurismo + $order[$i]->efectivoRecibido;

                                    $dataObj->turismoTime += $tCounterTurismo;
                                    $dataObj->turismoMoney += $mCounterTurismo;
                                    $dataObj->turismoOver20kms += $o20CounterTurismo;
                                    break;
                                case 2:
                                    $dataObj->pickup++;

                                    if ($order[$i]->tiempo != null) {
                                        if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                            $stime = explode(' ', $order[$i]->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPickup = $o20CounterPickup + intval($time);
                                            }
                                            $order[$i]->tiempo = 40 + intval($time);
                                            $tCounterPickup = $tCounterPickup + intval($order[$i]->tiempo);
                                        } else {
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPickup = $o20CounterPickup + intval($order[$i]->tiempo);
                                            }
                                            $order[$i]->tiempo = 40 + intval($order[$i]->tiempo);
                                            $tCounterPickup = $tCounterPickup + intval($order[$i]->tiempo);
                                        }
                                    }
                                    $mCounterPickup = $mCounterPickup + $order[$i]->efectivoRecibido;

                                    $dataObj->pickupTime += $tCounterPickup;
                                    $dataObj->pickupMoney += $mCounterPickup;
                                    $dataObj->pickupOver20kms += $o20CounterPickup;
                                    break;
                                case 3:
                                    $dataObj->panel++;

                                    if ($order[$i]->tiempo != null) {
                                        if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                            $stime = explode(' ', $order[$i]->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPanel = $o20CounterPanel + intval($time);
                                            }
                                            $order[$i]->tiempo = 40 + intval($time);
                                            $tCounterPanel = $tCounterPanel + intval($order[$i]->tiempo);
                                        } else {
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPanel = $o20CounterPanel + intval($order[$i]->tiempo);
                                            }
                                            $order[$i]->tiempo = 40 + intval($order[$i]->tiempo);
                                            $tCounterPanel = $tCounterPanel + intval($order[$i]->tiempo);
                                        }
                                    }
                                    $mCounterPanel = $mCounterPanel + $order[$i]->efectivoRecibido;

                                    $dataObj->panelTime += $tCounterPanel;
                                    $dataObj->panelMoney += $mCounterPanel;
                                    $dataObj->panelOver20kms += $o20CounterPanel;
                                    break;
                                case 4:
                                    $dataObj->pickupAuxiliar++;

                                    if ($order[$i]->tiempo != null && $order[$i]->idAuxiliar != $driver->idUsuario) {
                                        if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                            $stime = explode(' ', $order[$i]->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                            }
                                            $order[$i]->tiempo = 40 + intval($time);
                                            $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($order[$i]->tiempo);
                                        } else {
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($order[$i]->tiempo);
                                            }
                                            $order[$i]->tiempo = 40 + intval($order[$i]->tiempo);
                                            $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($order[$i]->tiempo);
                                        }
                                        $mCounterPickupAuxiliar = $mCounterPickupAuxiliar + $order[$i]->efectivoRecibido;
                                    } else if ($order[$i]->idAuxiliar == $driver->idUsuario) {
                                        if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                            $stime = explode(' ', $order[$i]->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                            }
                                        } else {
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($order[$i]->tiempo);
                                            }
                                        }
                                    }

                                    $dataObj->pickupAuxiliarTime += $tCounterPickupAuxiliar;
                                    $dataObj->pickupAuxiliarMoney += $mCounterPickupAuxiliar;
                                    $dataObj->pickupAuxiliarOver20kms += $o20CounterPickupAuxiliar;
                                    break;
                                case 11:
                                    $dataObj->pickupAuxiliar++;

                                    if ($order[$i]->tiempo != null && $order[$i]->idAuxiliar != $driver->idUsuario) {
                                        if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                            $stime = explode(' ', $order[$i]->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                            }
                                            $order[$i]->tiempo = 40 + intval($time);
                                            $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($order[$i]->tiempo);
                                        } else {
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($order[$i]->tiempo);
                                            }
                                            $order[$i]->tiempo = 40 + intval($order[$i]->tiempo);
                                            $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($order[$i]->tiempo);
                                        }
                                        $mCounterPickupAuxiliar = $mCounterPickupAuxiliar + $order[$i]->efectivoRecibido;
                                    } else if ($order[$i]->idAuxiliar == $driver->idUsuario) {
                                        if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                            $stime = explode(' ', $order[$i]->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                            }
                                        } else {
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($order[$i]->tiempo);
                                            }
                                        }
                                    }

                                    $dataObj->pickupAuxiliarTime += $tCounterPickupAuxiliar;
                                    $dataObj->pickupAuxiliarMoney += $mCounterPickupAuxiliar;
                                    $dataObj->pickupAuxiliarOver20kms += $o20CounterPickupAuxiliar;
                                    break;
                                case 12:
                                    $dataObj->pickupAuxiliar++;

                                    if ($order[$i]->tiempo != null && $order[$i]->idAuxiliar != $driver->idUsuario) {
                                        if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                            $stime = explode(' ', $order[$i]->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                            }
                                            $order[$i]->tiempo = 40 + intval($time);
                                            $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($order[$i]->tiempo);
                                        } else {
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($order[$i]->tiempo);
                                            }
                                            $order[$i]->tiempo = 40 + intval($order[$i]->tiempo);
                                            $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($order[$i]->tiempo);
                                        }
                                        $mCounterPickupAuxiliar = $mCounterPickupAuxiliar + $order[$i]->efectivoRecibido;
                                    } else if ($order[$i]->idAuxiliar == $driver->idUsuario) {
                                        if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                            $stime = explode(' ', $order[$i]->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                            }
                                        } else {
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($order[$i]->tiempo);
                                            }
                                        }
                                    }

                                    $dataObj->pickupAuxiliarTime += $tCounterPickupAuxiliar;
                                    $dataObj->pickupAuxiliarMoney += $mCounterPickupAuxiliar;
                                    $dataObj->pickupAuxiliarOver20kms += $o20CounterPickupAuxiliar;
                                    break;
                                case 5:
                                    $dataObj->panelAuxiliar++;

                                    if ($order[$i]->tiempo != null && $order[$i]->idAuxiliar != $driver->idUsuario) {
                                        if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                            $stime = explode(' ', $order[$i]->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPanelAuxiliar = $o20CounterPanelAuxiliar + intval($time);
                                            }
                                            $order[$i]->tiempo = 40 + intval($time);
                                            $tCounterPanelAuxiliar = $tCounterPanelAuxiliar + intval($order[$i]->tiempo);
                                        } else {
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPanelAuxiliar = $o20CounterPanelAuxiliar + intval($order[$i]->tiempo);
                                            }
                                            $order[$i]->tiempo = 40 + intval($order[$i]->tiempo);
                                            $tCounterPanelAuxiliar = $tCounterPanelAuxiliar + intval($order[$i]->tiempo);
                                        }
                                        $mCounterPanelAuxiliar = $mCounterPanelAuxiliar + $order[$i]->efectivoRecibido;
                                    } else if ($order[$i]->idAuxiliar == $driver->idUsuario) {
                                        if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                            $stime = explode(' ', $order[$i]->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPanelAuxiliar = $o20CounterPanelAuxiliar + intval($time);
                                            }
                                        } else {
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterPanelAuxiliar = $o20CounterPanelAuxiliar + intval($order[$i]->tiempo);
                                            }
                                        }
                                    }

                                    $dataObj->panelAuxiliarTime += $tCounterPanelAuxiliar;
                                    $dataObj->panelAuxiliarMoney += $mCounterPanelAuxiliar;
                                    $dataObj->panelAuxiliarOver20kms += $o20CounterPanelAuxiliar;
                                    break;
                                case 7:
                                    $dataObj->transTurism++;

                                    if ($order[$i]->tiempo != null) {
                                        if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                            $stime = explode(' ', $order[$i]->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterTransTurism = $o20CounterTransTurism + intval($time);
                                            }
                                            $order[$i]->tiempo = 20 + intval($time);
                                            $tCounterTransTurism = $tCounterTransTurism + intval($order[$i]->tiempo);
                                        } else {
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterTransTurism = $o20CounterTransTurism + intval($order[$i]->tiempo);
                                            }
                                            $order[$i]->tiempo = 20 + intval($order[$i]->tiempo);
                                            $tCounterTransTurism = $tCounterTransTurism + intval($order[$i]->tiempo);
                                        }
                                    }
                                    $mCounterTransTurism = $mCounterTransTurism + $order[$i]->efectivoRecibido;

                                    $dataObj->transTurismTime += $tCounterTransTurism;
                                    $dataObj->transTurismMoney += $mCounterTransTurism;
                                    $dataObj->transTurismOver20kms += $o20CounterTransTurism;
                                    break;
                                case 8:
                                    $dataObj->camion11++;

                                    if ($order[$i]->tiempo != null && $order[$i]->idAuxiliar != $driver->idUsuario) {
                                        if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                            $stime = explode(' ', $order[$i]->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterCamion11 = $o20CounterCamion11 + intval($time);
                                            }
                                            $order[$i]->tiempo = 70 + intval($time);
                                            $tCounterCamion11 = $tCounterCamion11 + intval($order[$i]->tiempo);
                                        } else {
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterCamion11 = $o20CounterCamion11 + intval($order[$i]->tiempo);
                                            }
                                            $order[$i]->tiempo = 70 + intval($order[$i]->tiempo);
                                            $tCounterCamion11 = $tCounterCamion11 + intval($order[$i]->tiempo);
                                        }
                                        $mCounterCamion11 = $mCounterCamion11 + $order[$i]->efectivoRecibido;
                                    } else if ($order[$i]->idAuxiliar == $driver->idUsuario) {
                                        if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                            $stime = explode(' ', $order[$i]->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterCamion11 = $o20CounterCamion11 + intval($time);
                                            }
                                        } else {
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounterCamion11 = $o20CounterCamion11 + intval($order[$i]->tiempo);
                                            }
                                        }
                                    }

                                    $dataObj->camion11Time += $tCounterCamion11;
                                    $dataObj->camion11Money += $mCounterCamion11;
                                    $dataObj->camion11Over20kms += $o20CounterCamion11;
                                    break;

                                case 10:
                                    $dataObj->nextDay++;

                                    if ($order[$i]->tiempo != null) {
                                        if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                            $stime = explode(" ", $order[$i]->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounternextDay = $o20CounternextDay + intval($time);
                                            }
                                            $order[$i]->tiempo = 20 + intval($time);
                                            $tCounternextDay = $tCounternextDay + intval($order[$i]->tiempo);
                                        } else {
                                            if (floatval($order[$i]->distancia) > 20) {
                                                $o20CounternextDay = $o20CounternextDay + intval($order[$i]->tiempo);
                                            }
                                            $order[$i]->tiempo = 20 + intval($order[$i]->tiempo);
                                            $tCounternextDay = $tCounternextDay + intval($order[$i]->tiempo);
                                        }
                                    }
                                    $mCounternextDay = $mCounternextDay + $order[$i]->efectivoRecibido;

                                    $dataObj->nextDayTime += $tCounternextDay;
                                    $dataObj->nextDayMoney += $mCounternextDay;
                                    $dataObj->nextDayOver20kms += $o20CounternextDay;
                                    break;
                            }

                            $dataObj->totalOrders = $dataObj->moto + $dataObj->turismo + $dataObj->pickup + $dataObj->panel + $dataObj->pickupAuxiliar + $dataObj->panelAuxiliar + $dataObj->transTurism + $dataObj->camion11 + $dataObj->nextDay;
                            $dataObj->totalTime = $dataObj->motoTime + $dataObj->turismoTime + $dataObj->pickupTime + $dataObj->panelTime + $dataObj->pickupAuxiliarTime + $dataObj->panelAuxiliarTime + $dataObj->transTurismTime + $dataObj->camion11Time + $dataObj->nextDayTime;
                            $dataObj->totalMoney = $dataObj->motoMoney + $dataObj->turismoMoney + $dataObj->pickupMoney + $dataObj->panelMoney + $dataObj->pickupAuxiliarMoney + $dataObj->panelAuxiliarMoney + $dataObj->transTurismMoney + $dataObj->camion11Money + $dataObj->nextDayMoney;
                            $dataObj->totalOver20kms = $dataObj->motoOver20kms + $dataObj->turismoOver20kms + $dataObj->pickupOver20kms + $dataObj->panelOver20kms + $dataObj->pickupAuxiliarOver20kms + $dataObj->panelAuxiliarOver20kms + $dataObj->transTurismOver20kms + $dataObj->camion11Over20kms + $dataObj->nextDayOver20kms;

                            $auxTime = DetalleDelivery::with('delivery')->whereIn('idEstado', [44, 46, 47])
                                ->where([
                                    'idAuxiliar' => $driver->idUsuario,
                                ])
                                ->whereHas('delivery', function ($q) {
                                    $q->whereIn('idCategoria', [4, 5, 8, 11, 12]);
                                })
                                ->whereDate('fechaEntrega', $dataObj->fecha)
                                ->get();

                            $auxCounter = 0;

                            foreach ($auxTime as $aux) {
                                if ($aux->tiempo != null) {
                                    if ($aux->delivery->idCategoria == 8) {
                                        if (strpos($aux->tiempo, 'hour')) {
                                            $stime = explode(' ', $aux->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);

                                            $aux->tiempo = 60 + intval($time);
                                            $auxCounter = $auxCounter + intval($aux->tiempo);
                                        } else {
                                            $aux->tiempo = 60 + intval($aux->tiempo);
                                            $auxCounter = $auxCounter + intval($aux->tiempo);
                                        }
                                    } else {
                                        if (strpos($aux->tiempo, 'hour')) {
                                            $stime = explode(' ', $aux->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);

                                            $aux->tiempo = (40 + intval($time)) - 10;
                                            $auxCounter = $auxCounter + intval($aux->tiempo);
                                        } else {
                                            $aux->tiempo = (40 + intval($aux->tiempo)) - 10;
                                            $auxCounter = $auxCounter + intval($aux->tiempo);
                                        }
                                    }

                                }
                            }
                            $dataObj->totalAuxTime = $auxCounter;

                            $extTime = DetalleDelivery::with('extraCharges')
                                ->whereIn('idEstado', [44, 46, 47])
                                ->where([
                                    'idConductor' => $driver->idUsuario,
                                ])
                                ->whereDate('fechaEntrega', $dataObj->fecha)
                                ->orWhere('idAuxiliar', $driver->idUsuario)
                                ->whereIn('idEstado', [44, 46, 47])
                                ->whereDate('fechaEntrega', $dataObj->fecha)
                                ->get();

                            $extCounter = 0;

                            foreach ($extTime as $ext) {
                                if (sizeof($ext->extraCharges) > 0) {
                                    foreach ($ext->extraCharges as $exCharge) {
                                        if (isset($exCharge->option->tiempo)) {
                                            $extCounter += $exCharge->option->tiempo;
                                        }
                                    }
                                }
                            }
                            $dataObj->totalExtraTime = $extCounter;
                            $dataObj->tiempototal = $dataObj->totalTime + $dataObj->totalOver20kms + $dataObj->totalAuxTime + $dataObj->totalExtraTime;
                        }

                        array_push($outputData, $dataObj);
                    }
                }
            } else {
                $orders = DetalleDelivery::with(['delivery'])
                    ->whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->where('idConductor', $driver)
                    ->orWhere('idAuxiliar', $driver)
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->orderBy('fechaEntrega', 'desc')
                    ->get()
                    ->groupBy(function ($val) {
                        return Carbon::parse($val->fechaEntrega)->format('Y-m-d');
                    });

                $driverDetails = User::where('idUsuario', $driver)->get()->first();

                foreach ($orders as $key => $order) {
                    $dataObj = (object)array();
                    $dataObj->driver = $driverDetails->nomUsuario;
                    $dataObj->fecha = $key;
                    $dataObj->moto = 0;
                    $dataObj->turismo = 0;
                    $dataObj->pickup = 0;
                    $dataObj->panel = 0;
                    $dataObj->pickupAuxiliar = 0;
                    $dataObj->panelAuxiliar = 0;
                    $dataObj->transTurism = 0;
                    $dataObj->camion11 = 0;
                    $dataObj->nextDay = 0;
                    $dataObj->motoTime = 0;
                    $dataObj->motoMoney = 0;
                    $dataObj->motoOver20kms = 0;
                    $dataObj->turismoTime = 0;
                    $dataObj->turismoMoney = 0;
                    $dataObj->turismoOver20kms = 0;
                    $dataObj->pickupTime = 0;
                    $dataObj->pickupMoney = 0;
                    $dataObj->pickupOver20kms = 0;
                    $dataObj->panelTime = 0;
                    $dataObj->panelMoney = 0;
                    $dataObj->panelOver20kms = 0;
                    $dataObj->pickupAuxiliarTime = 0;
                    $dataObj->pickupAuxiliarMoney = 0;
                    $dataObj->pickupAuxiliarOver20kms = 0;
                    $dataObj->panelAuxiliarTime = 0;
                    $dataObj->panelAuxiliarMoney = 0;
                    $dataObj->panelAuxiliarOver20kms = 0;
                    $dataObj->transTurismTime = 0;
                    $dataObj->transTurismMoney = 0;
                    $dataObj->transTurismOver20kms = 0;
                    $dataObj->camion11Time = 0;
                    $dataObj->camion11Money = 0;
                    $dataObj->camion11Over20kms = 0;
                    $dataObj->nextDayTime = 0;
                    $dataObj->nextDayMoney = 0;
                    $dataObj->nextDayOver20kms = 0;

                    for ($i = 0; $i < sizeof($order); $i++) {
                        $tCounterMoto = 0;
                        $mCounterMoto = 0;
                        $o20CounterMoto = 0;
                        $tCounterTurismo = 0;
                        $mCounterTurismo = 0;
                        $o20CounterTurismo = 0;
                        $tCounterPickup = 0;
                        $mCounterPickup = 0;
                        $o20CounterPickup = 0;
                        $tCounterPanel = 0;
                        $mCounterPanel = 0;
                        $o20CounterPanel = 0;
                        $tCounterPickupAuxiliar = 0;
                        $mCounterPickupAuxiliar = 0;
                        $o20CounterPickupAuxiliar = 0;
                        $tCounterPanelAuxiliar = 0;
                        $mCounterPanelAuxiliar = 0;
                        $o20CounterPanelAuxiliar = 0;
                        $tCounterTransTurism = 0;
                        $mCounterTransTurism = 0;
                        $o20CounterTransTurism = 0;
                        $tCounterCamion11 = 0;
                        $mCounterCamion11 = 0;
                        $o20CounterCamion11 = 0;
                        $tCounternextDay = 0;
                        $mCounternextDay = 0;
                        $o20CounternextDay = 0;

                        switch ($order[$i]->delivery->idCategoria) {
                            case 6:
                                $dataObj->moto++;

                                if ($order[$i]->tiempo != null) {
                                    if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                        $stime = explode(' ', $order[$i]->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterMoto = $o20CounterMoto + intval($time);
                                        }
                                        $order[$i]->tiempo = 20 + intval($time);
                                        $tCounterMoto = $tCounterMoto + intval($order[$i]->tiempo);
                                    } else {
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterMoto = $o20CounterMoto + intval($order[$i]->tiempo);
                                        }
                                        $order[$i]->tiempo = 20 + intval($order[$i]->tiempo);
                                        $tCounterMoto = $tCounterMoto + intval($order[$i]->tiempo);
                                    }
                                }
                                $mCounterMoto = $mCounterMoto + $order[$i]->efectivoRecibido;

                                $dataObj->motoTime += $tCounterMoto;
                                $dataObj->motoMoney += $mCounterMoto;
                                $dataObj->motoOver20kms += $o20CounterMoto;
                                break;
                            case 1:
                                $dataObj->turismo++;

                                if ($order[$i]->tiempo != null) {
                                    if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                        $stime = explode(" ", $order[$i]->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterTurismo = $o20CounterTurismo + intval($time);
                                        }
                                        $order[$i]->tiempo = 20 + intval($time);
                                        $tCounterTurismo = $tCounterTurismo + intval($order[$i]->tiempo);
                                    } else {
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterTurismo = $o20CounterTurismo + intval($order[$i]->tiempo);
                                        }
                                        $order[$i]->tiempo = 20 + intval($order[$i]->tiempo);
                                        $tCounterTurismo = $tCounterTurismo + intval($order[$i]->tiempo);
                                    }
                                }
                                $mCounterTurismo = $mCounterTurismo + $order[$i]->efectivoRecibido;

                                $dataObj->turismoTime += $tCounterTurismo;
                                $dataObj->turismoMoney += $mCounterTurismo;
                                $dataObj->turismoOver20kms += $o20CounterTurismo;
                                break;
                            case 13:
                                $dataObj->turismo++;

                                if ($order[$i]->tiempo != null) {
                                    if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                        $stime = explode(" ", $order[$i]->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterTurismo = $o20CounterTurismo + intval($time);
                                        }
                                        $order[$i]->tiempo = 20 + intval($time);
                                        $tCounterTurismo = $tCounterTurismo + intval($order[$i]->tiempo);
                                    } else {
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterTurismo = $o20CounterTurismo + intval($order[$i]->tiempo);
                                        }
                                        $order[$i]->tiempo = 20 + intval($order[$i]->tiempo);
                                        $tCounterTurismo = $tCounterTurismo + intval($order[$i]->tiempo);
                                    }
                                }
                                $mCounterTurismo = $mCounterTurismo + $order[$i]->efectivoRecibido;

                                $dataObj->turismoTime += $tCounterTurismo;
                                $dataObj->turismoMoney += $mCounterTurismo;
                                $dataObj->turismoOver20kms += $o20CounterTurismo;
                                break;
                            case 2:
                                $dataObj->pickup++;

                                if ($order[$i]->tiempo != null) {
                                    if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                        $stime = explode(' ', $order[$i]->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPickup = $o20CounterPickup + intval($time);
                                        }
                                        $order[$i]->tiempo = 40 + intval($time);
                                        $tCounterPickup = $tCounterPickup + intval($order[$i]->tiempo);
                                    } else {
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPickup = $o20CounterPickup + intval($order[$i]->tiempo);
                                        }
                                        $order[$i]->tiempo = 40 + intval($order[$i]->tiempo);
                                        $tCounterPickup = $tCounterPickup + intval($order[$i]->tiempo);
                                    }
                                }
                                $mCounterPickup = $mCounterPickup + $order[$i]->efectivoRecibido;

                                $dataObj->pickupTime += $tCounterPickup;
                                $dataObj->pickupMoney += $mCounterPickup;
                                $dataObj->pickupOver20kms += $o20CounterPickup;
                                break;
                            case 3:
                                $dataObj->panel++;

                                if ($order[$i]->tiempo != null) {
                                    if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                        $stime = explode(' ', $order[$i]->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPanel = $o20CounterPanel + intval($time);
                                        }
                                        $order[$i]->tiempo = 40 + intval($time);
                                        $tCounterPanel = $tCounterPanel + intval($order[$i]->tiempo);
                                    } else {
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPanel = $o20CounterPanel + intval($order[$i]->tiempo);
                                        }
                                        $order[$i]->tiempo = 40 + intval($order[$i]->tiempo);
                                        $tCounterPanel = $tCounterPanel + intval($order[$i]->tiempo);
                                    }
                                }
                                $mCounterPanel = $mCounterPanel + $order[$i]->efectivoRecibido;

                                $dataObj->panelTime += $tCounterPanel;
                                $dataObj->panelMoney += $mCounterPanel;
                                $dataObj->panelOver20kms += $o20CounterPanel;
                                break;
                            case 4:
                                $dataObj->pickupAuxiliar++;

                                if ($order[$i]->tiempo != null && $order[$i]->idAuxiliar != $driver) {
                                    if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                        $stime = explode(' ', $order[$i]->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                        }
                                        $order[$i]->tiempo = 40 + intval($time);
                                        $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($order[$i]->tiempo);
                                    } else {
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($order[$i]->tiempo);
                                        }
                                        $order[$i]->tiempo = 40 + intval($order[$i]->tiempo);
                                        $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($order[$i]->tiempo);
                                    }
                                    $mCounterPickupAuxiliar = $mCounterPickupAuxiliar + $order[$i]->efectivoRecibido;
                                } else if ($order[$i]->idAuxiliar == $driver) {
                                    if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                        $stime = explode(' ', $order[$i]->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                        }
                                    } else {
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($order[$i]->tiempo);
                                        }
                                    }
                                }

                                $dataObj->pickupAuxiliarTime += $tCounterPickupAuxiliar;
                                $dataObj->pickupAuxiliarMoney += $mCounterPickupAuxiliar;
                                $dataObj->pickupAuxiliarOver20kms += $o20CounterPickupAuxiliar;
                                break;
                            case 11:
                                $dataObj->pickupAuxiliar++;

                                if ($order[$i]->tiempo != null && $order[$i]->idAuxiliar != $driver) {
                                    if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                        $stime = explode(' ', $order[$i]->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                        }
                                        $order[$i]->tiempo = 40 + intval($time);
                                        $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($order[$i]->tiempo);
                                    } else {
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($order[$i]->tiempo);
                                        }
                                        $order[$i]->tiempo = 40 + intval($order[$i]->tiempo);
                                        $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($order[$i]->tiempo);
                                    }
                                    $mCounterPickupAuxiliar = $mCounterPickupAuxiliar + $order[$i]->efectivoRecibido;
                                } else if ($order[$i]->idAuxiliar == $driver) {
                                    if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                        $stime = explode(' ', $order[$i]->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                        }
                                    } else {
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($order[$i]->tiempo);
                                        }
                                    }
                                }

                                $dataObj->pickupAuxiliarTime += $tCounterPickupAuxiliar;
                                $dataObj->pickupAuxiliarMoney += $mCounterPickupAuxiliar;
                                $dataObj->pickupAuxiliarOver20kms += $o20CounterPickupAuxiliar;
                                break;
                            case 12:
                                $dataObj->pickupAuxiliar++;

                                if ($order[$i]->tiempo != null && $order[$i]->idAuxiliar != $driver) {
                                    if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                        $stime = explode(' ', $order[$i]->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                        }
                                        $order[$i]->tiempo = 40 + intval($time);
                                        $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($order[$i]->tiempo);
                                    } else {
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($order[$i]->tiempo);
                                        }
                                        $order[$i]->tiempo = 40 + intval($order[$i]->tiempo);
                                        $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($order[$i]->tiempo);
                                    }
                                    $mCounterPickupAuxiliar = $mCounterPickupAuxiliar + $order[$i]->efectivoRecibido;
                                } else if ($order[$i]->idAuxiliar == $driver) {
                                    if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                        $stime = explode(' ', $order[$i]->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                        }
                                    } else {
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($order[$i]->tiempo);
                                        }
                                    }
                                }

                                $dataObj->pickupAuxiliarTime += $tCounterPickupAuxiliar;
                                $dataObj->pickupAuxiliarMoney += $mCounterPickupAuxiliar;
                                $dataObj->pickupAuxiliarOver20kms += $o20CounterPickupAuxiliar;
                                break;
                            case 5:
                                $dataObj->panelAuxiliar++;

                                if ($order[$i]->tiempo != null && $order[$i]->idAuxiliar != $driver) {
                                    if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                        $stime = explode(' ', $order[$i]->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPanelAuxiliar = $o20CounterPanelAuxiliar + intval($time);
                                        }
                                        $order[$i]->tiempo = 40 + intval($time);
                                        $tCounterPanelAuxiliar = $tCounterPanelAuxiliar + intval($order[$i]->tiempo);
                                    } else {
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPanelAuxiliar = $o20CounterPanelAuxiliar + intval($order[$i]->tiempo);
                                        }
                                        $order[$i]->tiempo = 40 + intval($order[$i]->tiempo);
                                        $tCounterPanelAuxiliar = $tCounterPanelAuxiliar + intval($order[$i]->tiempo);
                                    }
                                    $mCounterPanelAuxiliar = $mCounterPanelAuxiliar + $order[$i]->efectivoRecibido;
                                } else if ($order[$i]->idAuxiliar == $driver) {
                                    if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                        $stime = explode(' ', $order[$i]->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPanelAuxiliar = $o20CounterPanelAuxiliar + intval($time);
                                        }
                                    } else {
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterPanelAuxiliar = $o20CounterPanelAuxiliar + intval($order[$i]->tiempo);
                                        }
                                    }
                                }

                                $dataObj->panelAuxiliarTime += $tCounterPanelAuxiliar;
                                $dataObj->panelAuxiliarMoney += $mCounterPanelAuxiliar;
                                $dataObj->panelAuxiliarOver20kms += $o20CounterPanelAuxiliar;
                                break;
                            case 7:
                                $dataObj->transTurism++;

                                if ($order[$i]->tiempo != null) {
                                    if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                        $stime = explode(' ', $order[$i]->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterTransTurism = $o20CounterTransTurism + intval($time);
                                        }
                                        $order[$i]->tiempo = 20 + intval($time);
                                        $tCounterTransTurism = $tCounterTransTurism + intval($order[$i]->tiempo);
                                    } else {
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterTransTurism = $o20CounterTransTurism + intval($order[$i]->tiempo);
                                        }
                                        $order[$i]->tiempo = 20 + intval($order[$i]->tiempo);
                                        $tCounterTransTurism = $tCounterTransTurism + intval($order[$i]->tiempo);
                                    }
                                }
                                $mCounterTransTurism = $mCounterTransTurism + $order[$i]->efectivoRecibido;

                                $dataObj->transTurismTime += $tCounterTransTurism;
                                $dataObj->transTurismMoney += $mCounterTransTurism;
                                $dataObj->transTurismOver20kms += $o20CounterTransTurism;
                                break;
                            case 8:
                                $dataObj->camion11++;

                                if ($order[$i]->tiempo != null && $order[$i]->idAuxiliar != $driver) {
                                    if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                        $stime = explode(' ', $order[$i]->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterCamion11 = $o20CounterCamion11 + intval($time);
                                        }
                                        $order[$i]->tiempo = 70 + intval($time);
                                        $tCounterCamion11 = $tCounterCamion11 + intval($order[$i]->tiempo);
                                    } else {
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterCamion11 = $o20CounterCamion11 + intval($order[$i]->tiempo);
                                        }
                                        $order[$i]->tiempo = 70 + intval($order[$i]->tiempo);
                                        $tCounterCamion11 = $tCounterCamion11 + intval($order[$i]->tiempo);
                                    }
                                    $mCounterCamion11 = $mCounterCamion11 + $order[$i]->efectivoRecibido;
                                } else if ($order[$i]->idAuxiliar == $driver) {
                                    if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                        $stime = explode(' ', $order[$i]->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterCamion11 = $o20CounterCamion11 + intval($time);
                                        }
                                    } else {
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounterCamion11 = $o20CounterCamion11 + intval($order[$i]->tiempo);
                                        }
                                    }
                                }

                                $dataObj->camion11Time += $tCounterCamion11;
                                $dataObj->camion11Money += $mCounterCamion11;
                                $dataObj->camion11Over20kms += $o20CounterCamion11;
                                break;

                            case 10:
                                $dataObj->nextDay++;

                                if ($order[$i]->tiempo != null) {
                                    if (strpos($order[$i]->tiempo, 'hour') || strpos($order[$i]->tiempo, 'h')) {
                                        $stime = explode(" ", $order[$i]->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounternextDay = $o20CounternextDay + intval($time);
                                        }
                                        $order[$i]->tiempo = 20 + intval($time);
                                        $tCounternextDay = $tCounternextDay + intval($order[$i]->tiempo);
                                    } else {
                                        if (floatval($order[$i]->distancia) > 20) {
                                            $o20CounternextDay = $o20CounternextDay + intval($order[$i]->tiempo);
                                        }
                                        $order[$i]->tiempo = 20 + intval($order[$i]->tiempo);
                                        $tCounternextDay = $tCounternextDay + intval($order[$i]->tiempo);
                                    }
                                }
                                $mCounternextDay = $mCounternextDay + $order[$i]->efectivoRecibido;

                                $dataObj->nextDayTime += $tCounternextDay;
                                $dataObj->nextDayMoney += $mCounternextDay;
                                $dataObj->nextDayOver20kms += $o20CounternextDay;
                                break;
                        }

                        $dataObj->totalOrders = $dataObj->moto + $dataObj->turismo + $dataObj->pickup + $dataObj->panel + $dataObj->pickupAuxiliar + $dataObj->panelAuxiliar + $dataObj->transTurism + $dataObj->camion11 + $dataObj->nextDay;
                        $dataObj->totalTime = $dataObj->motoTime + $dataObj->turismoTime + $dataObj->pickupTime + $dataObj->panelTime + $dataObj->pickupAuxiliarTime + $dataObj->panelAuxiliarTime + $dataObj->transTurismTime + $dataObj->camion11Time + $dataObj->nextDayTime;
                        $dataObj->totalMoney = $dataObj->motoMoney + $dataObj->turismoMoney + $dataObj->pickupMoney + $dataObj->panelMoney + $dataObj->pickupAuxiliarMoney + $dataObj->panelAuxiliarMoney + $dataObj->transTurismMoney + $dataObj->camion11Money + $dataObj->nextDayMoney;
                        $dataObj->totalOver20kms = $dataObj->motoOver20kms + $dataObj->turismoOver20kms + $dataObj->pickupOver20kms + $dataObj->panelOver20kms + $dataObj->pickupAuxiliarOver20kms + $dataObj->panelAuxiliarOver20kms + $dataObj->transTurismOver20kms + $dataObj->camion11Over20kms + $dataObj->nextDayOver20kms;

                        $auxTime = DetalleDelivery::with('delivery')->whereIn('idEstado', [44, 46, 47])
                            ->where([
                                'idAuxiliar' => $driver,
                            ])
                            ->whereHas('delivery', function ($q) {
                                $q->whereIn('idCategoria', [4, 5, 8, 11, 12]);
                            })
                            ->whereDate('fechaEntrega', $dataObj->fecha)
                            ->get();

                        $auxCounter = 0;

                        foreach ($auxTime as $aux) {
                            if ($aux->tiempo != null) {
                                if ($aux->delivery->idCategoria == 8) {
                                    if (strpos($aux->tiempo, 'hour')) {
                                        $stime = explode(' ', $aux->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);

                                        $aux->tiempo = 60 + intval($time);
                                        $auxCounter = $auxCounter + intval($aux->tiempo);
                                    } else {
                                        $aux->tiempo = 60 + intval($aux->tiempo);
                                        $auxCounter = $auxCounter + intval($aux->tiempo);
                                    }
                                } else {
                                    if (strpos($aux->tiempo, 'hour')) {
                                        $stime = explode(' ', $aux->tiempo);
                                        $time = intval($stime[0]) * 60 + intval($stime[2]);

                                        $aux->tiempo = (40 + intval($time)) - 10;
                                        $auxCounter = $auxCounter + intval($aux->tiempo);
                                    } else {
                                        $aux->tiempo = (40 + intval($aux->tiempo)) - 10;
                                        $auxCounter = $auxCounter + intval($aux->tiempo);
                                    }
                                }

                            }
                        }
                        $dataObj->totalAuxTime = $auxCounter;

                        $extTime = DetalleDelivery::with('extraCharges')
                            ->whereIn('idEstado', [44, 46, 47])
                            ->where([
                                'idConductor' => $driver,
                            ])
                            ->whereDate('fechaEntrega', $dataObj->fecha)
                            ->orWhere('idAuxiliar', $driver)
                            ->whereIn('idEstado', [44, 46, 47])
                            ->whereDate('fechaEntrega', $dataObj->fecha)
                            ->get();

                        $extCounter = 0;

                        foreach ($extTime as $ext) {
                            if (sizeof($ext->extraCharges) > 0) {
                                foreach ($ext->extraCharges as $exCharge) {
                                    $extCounter += $exCharge->option->tiempo;
                                }
                            }
                        }
                        $dataObj->totalExtraTime = $extCounter;

                        $dataObj->tiempototal = $dataObj->totalTime + $dataObj->totalOver20kms + $dataObj->totalAuxTime + $dataObj->totalExtraTime;
                    }

                    array_push($outputData, $dataObj);
                }
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => $outputData,
                ],
                200
            );
        } catch (Exception $ex) {
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

    //Report order by customer

    public function reportOrdersByCustomer(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.customerId' => 'required',
            'form.initDate' => 'required',
            'form.finDate' => 'required'
        ]);

        $form = $request->form;
        $customer = $form['customerId'];
        if ($customer != -1) {
            $customerDetails = DeliveryClient::where('idCliente', $customer)->get()->first();
        }

        $initDateTime = new Carbon(date('Y-m-d', strtotime($form['initDate'])) . ' 00:00:00');
        $finDateTime = new Carbon(date('Y-m-d', strtotime($form['finDate'])) . ' 23:59:59');

        try {
            $outputData = [];

            $categories = Category::where('isActivo', 1)->get();
            $ordersByCatArray = [];

            foreach ($categories as $category) {
                $mydataObj = (object)array();
                $mydataObj->category = $category->descCategoria;
                $mydataObj->orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->whereHas('delivery', function ($q) use ($customerDetails, $category) {
                        $q->where('idCliente', $customerDetails->idCliente)->where('idCategoria', $category->idCategoria);
                    })->count();
                $mydataObj->totalSurcharges = number_format(DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->whereHas('delivery', function ($q) use ($customerDetails, $category) {
                        $q->where('idCliente', $customerDetails->idCliente)->where('idCategoria', $category->idCategoria);
                    })->sum('recargo'), 2);

                $mydataObj->totalExtraCharges = number_format(DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->whereHas('delivery', function ($q) use ($customerDetails, $category) {
                        $q->where('idCliente', $customerDetails->idCliente)->where('idCategoria', $category->idCategoria);
                    })->sum('cargosExtra'), 2);

                $mydataObj->cTotal = number_format(DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->whereHas('delivery', function ($q) use ($customerDetails, $category) {
                        $q->where('idCliente', $customerDetails->idCliente)->where('idCategoria', $category->idCategoria);
                    })->sum('cTotal'), 2);

                if ($mydataObj->orders > 0) {
                    $exists = 0;
                    foreach ($outputData as $output) {
                        if ($mydataObj->category == $output->category) {
                            $exists++;
                        }
                    }

                    if ($exists == 0) {
                        array_push($ordersByCatArray, $mydataObj);
                    }
                }
            }

            $totalOrders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                ->whereHas('delivery', function ($q) use ($customerDetails) {
                    $q->where('idCliente', $customerDetails->idCliente);
                })->count();

            /*if ($customer == -1 && $isSameDay) {
                $orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])->get();

                foreach ($customers as $custr) {

                    foreach ($orders as $order) {
                        $dataObj = (object)array();
                        $dataObj->fecha = Carbon::parse($order->fechaEntrega)->format('Y-m-d');
                        $dataObj->customer = $custr->nomEmpresa;
                        $dataObj->orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                            ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                            ->whereHas('delivery', function ($q) use ($custr) {
                                $q->where('idCliente', $custr->idCliente);
                            })->count();

                        if ($dataObj->orders > 0) {
                            $exist = 0;
                            foreach ($outputData as $output) {
                                if ($dataObj->customer == $output->customer) {
                                    $exist++;
                                }
                            }

                            if ($exist == 0) {
                                array_push($outputData, $dataObj);
                            }

                        }

                    }

                }

                return response()->json(
                    [
                        'error' => 0,
                        'data' => array('ordersReport' => $outputData)
                    ],
                    200
                );

            } else if ($customer == -1 && !$isSameDay) {
                $datedOrders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])->orderBy('fechaEntrega', 'desc')->get()
                    ->groupBy(function ($val) {
                        return Carbon::parse($val->fechaEntrega)->format('Y-m-d');
                    });

                foreach ($customers as $custr) {
                    foreach ($datedOrders as $dOrders) {
                        foreach ($dOrders as $order) {

                            if ($custr->idCliente == $order->delivery->idCliente) {
                                $dataObj = (object)array();
                                $dataObj->customer = $custr->nomEmpresa;
                                $dataObj->fecha = Carbon::parse($order->fechaEntrega)->format('Y-m-d');
                                $initDateTime = new Carbon(date('Y-m-d', strtotime($dataObj->fecha)) . ' 00:00:00');
                                $finDateTime = new Carbon(date('Y-m-d', strtotime($dataObj->fecha)) . ' 23:59:59');
                                $dataObj->orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                                    ->whereHas('delivery', function ($q) use ($custr) {
                                        $q->where('idCliente', $custr->idCliente);
                                    })->count();

                                if ($dataObj->orders > 0) {
                                    $exist = 0;
                                    foreach ($outputData as $output) {
                                        if ($dataObj->fecha == $output->fecha && $dataObj->customer == $output->customer) {
                                            $exist++;
                                        }
                                    }

                                    if ($exist == 0) {
                                        array_push($outputData, $dataObj);
                                    }
                                }


                            }
                        }
                    }

                }

                return response()->json(
                    [
                        'error' => 0,
                        'data' => array(
                            'ordersReport' => $outputData
                        )
                    ],
                    200
                );


            } else*/
            /*if ($customer != -1 && $isSameDay) {
                $orders = DetalleDelivery::with(['estado', 'extraCharges.extracharge', 'conductor'])
                ->whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->whereHas('delivery', function ($q) use ($customerDetails) {
                        $q->where('idCliente', $customerDetails->idCliente);
                    })->get();

                if (sizeof($orders) > 0) {
                    $ordersInRange = 0;
                    foreach ($orders as $order) {
                        $order->recargo = number_format($order->recargo, 2);
                        $order->cTotal = number_format($order->cTotal, 2);
                        $order->cargosExtra = number_format($order->cargosExtra, 2);
                        $dataObj = (object)array();
                        $dataObj->customer = $customerDetails->nomEmpresa;
                        $dataObj->fecha = Carbon::parse($order->fechaEntrega)->format('Y-m-d');
                        $dataObj->orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                            ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                            ->whereHas('delivery', function ($q) use ($customerDetails) {
                                $q->where('idCliente', $customerDetails->idCliente);
                            })->count();

                        if ($dataObj->orders > 0) {
                            $exist = 0;
                            foreach ($outputData as $output) {
                                if ($dataObj->fecha == $output->fecha && $dataObj->customer == $output->customer) {
                                    $exist++;
                                }
                            }

                            if ($exist == 0) {
                                $ordersInRange = number_format($ordersInRange + $dataObj->orders);
                                array_push($outputData, $dataObj);
                            }
                        }
                    }
                    $tempSurSum = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                        ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                        ->whereHas('delivery', function ($q) use ($customerDetails) {
                            $q->where('idCliente', $customerDetails->idCliente);
                        })->sum('recargo');

                    $tempECSum = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                        ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                        ->whereHas('delivery', function ($q) use ($customerDetails) {
                            $q->where('idCliente', $customerDetails->idCliente);
                        })->sum('cargosExtra');

                    $tempCostSum = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                        ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                        ->whereHas('delivery', function ($q) use ($customerDetails) {
                            $q->where('idCliente', $customerDetails->idCliente);
                        })->sum('cTotal');

                    $totalSurcharges = number_format($tempSurSum, 2);
                    $totalCosts = number_format($tempCostSum, 2);
                    $totalExtraCharges = number_format($tempECSum, 2);
                }

                return response()->json(
                    [
                        'error' => 0,
                        'data' => array(
                            'ordersReport' => $outputData,
                            'totalOrders' => $totalOrders,
                            'totalSurcharges' => $totalSurcharges,
                            'totalExtraCharges' => $totalExtraCharges,
                            'totalCosts' => $totalCosts,
                            'ordersInRange' => $ordersInRange,
                            'ordersByCategory' => $ordersByCatArray,
                            'orders' => $orders
                        )
                    ],
                    200
                );
            } else if ($customer != -1 && !$isSameDay) {*/
            $orders = DetalleDelivery::with(['estado'])->whereIn('idEstado', [44, 46, 47])
                ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                ->whereHas('delivery', function ($q) use ($customerDetails) {
                    $q->where('idCliente', $customerDetails->idCliente);
                })
                ->orderBy('fechaEntrega', 'desc')->get()
                ->groupBy(function ($val) {
                    return Carbon::parse($val->fechaEntrega)->format('Y-m-d');
                });

            foreach ($orders as $order) {

                for ($i = 0; $i < sizeof($order); $i++) {

                    $data = (object)array();
                    $data->customer = $customerDetails->nomEmpresa;
                    $data->fecha = Carbon::parse($order[$i]->fechaEntrega)->format('Y-m-d');
                    $initDateTime = new Carbon(date('Y-m-d', strtotime($data->fecha)) . ' 00:00:00');
                    $finDateTime = new Carbon(date('Y-m-d', strtotime($data->fecha)) . ' 23:59:59');
                    $data->orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                        ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                        ->whereHas('delivery', function ($q) use ($customerDetails) {
                            $q->where('idCliente', $customerDetails->idCliente);
                        })->count();
                }
                if ($data->orders > 0) {
                    array_push($outputData, $data);
                }
            }

            $initDateTime = new Carbon(date('Y-m-d', strtotime($form['initDate'])) . ' 00:00:00');
            $finDateTime = new Carbon(date('Y-m-d', strtotime($form['finDate'])) . ' 23:59:59');

            $orders = DetalleDelivery::with(['estado', 'extraCharges.extracharge', 'conductor'])->whereIn('idEstado', [44, 46, 47])
                ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                ->whereHas('delivery', function ($q) use ($customerDetails) {
                    $q->where('idCliente', $customerDetails->idCliente);
                })->get();
            $ordersInRange = sizeof($orders);
            foreach ($orders as $order) {
                $order->recargo = number_format($order->recargo, 2);
                $order->cargosExtra = number_format($order->cargosExtra, 2);
                $order->cTotal = number_format($order->cTotal, 2);
            }

            $tempSurSum = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                ->whereHas('delivery', function ($q) use ($customerDetails) {
                    $q->where('idCliente', $customerDetails->idCliente);
                })->sum('recargo');

            $tempECSum = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                ->whereHas('delivery', function ($q) use ($customerDetails) {
                    $q->where('idCliente', $customerDetails->idCliente);
                })->sum('cargosExtra');

            $tempCostSum = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                ->whereHas('delivery', function ($q) use ($customerDetails) {
                    $q->where('idCliente', $customerDetails->idCliente);
                })->sum('cTotal');

            $totalSurcharges = number_format($tempSurSum, 2);
            $totalCosts = number_format($tempCostSum, 2);
            $totalExtraCharges = number_format($tempECSum, 2);
            return response()->json(
                [
                    'error' => 0,
                    'data' => array(
                        'ordersReport' => $outputData,
                        'totalOrders' => $totalOrders,
                        'ordersByCategory' => $ordersByCatArray,
                        'totalSurcharges' => $totalSurcharges,
                        'totalExtraCharges' => $totalExtraCharges,
                        'totalCosts' => $totalCosts,
                        'ordersInRange' => $ordersInRange,
                        'orders' => $orders
                    )
                ],
                200
            );
            //}
        } catch (Exception $ex) {
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

    //Reporte de envíos
    public function deliveriesReport(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.initDate' => 'required',
            'form.finDate' => 'required'
        ]);

        $form = $request->form;

        $initDateTime = new Carbon(date('Y-m-d', strtotime($form['initDate'])) . ' 00:00:00');
        $finDateTime = new Carbon(date('Y-m-d', strtotime($form['finDate'])) . ' 23:59:59');

        try {
            $outputData = [];

            $categories = Category::where('isActivo', 1)->get();
            $customers = DeliveryClient::where('isActivo', 1)->get();
            $ordersByCatArray = [];

            foreach ($categories as $category) {
                $mydataObj = (object)array();
                $mydataObj->category = $category->descCategoria;
                $mydataObj->orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->whereHas('delivery', function ($q) use ($category) {
                        $q->where('idCategoria', $category->idCategoria);
                    })->count();

                $mydataObj->totalSurcharges = number_format(DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->whereHas('delivery', function ($q) use ($category) {
                        $q->where('idCategoria', $category->idCategoria);
                    })->sum('recargo'), 2);

                $mydataObj->totalExtraCharges = number_format(DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->whereHas('delivery', function ($q) use ($category) {
                        $q->where('idCategoria', $category->idCategoria);
                    })->sum('cargosExtra'), 2);

                $mydataObj->cTotal = number_format(DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->whereHas('delivery', function ($q) use ($category) {
                        $q->where('idCategoria', $category->idCategoria);
                    })->sum('cTotal'), 2);

                if ($mydataObj->orders > 0) {
                    $exists = 0;
                    foreach ($outputData as $output) {
                        if ($mydataObj->category == $output->category) {
                            $exists++;
                        }
                    }

                    if ($exists == 0) {
                        array_push($ordersByCatArray, $mydataObj);
                    }
                }
            }

            $orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                ->get();
            $ordersInRange = sizeof($orders);
            foreach ($orders as $order) {
                $order->recargo = number_format($order->recargo, 2);
                $order->cargosExtra = number_format($order->cargosExtra, 2);
                $order->cTotal = number_format($order->cTotal, 2);
            }

            $tempSurSum = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                ->sum('recargo');

            $tempECSum = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                ->sum('cargosExtra');

            $tempCostSum = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                ->sum('cTotal');

            $totalSurcharges = number_format($tempSurSum, 2);
            $totalCosts = number_format($tempCostSum, 2);
            $totalExtraCharges = number_format($tempECSum, 2);

            $orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                ->orderBy('fechaEntrega', 'desc')
                ->get()
                ->groupBy(function ($val) {
                    return Carbon::parse($val->fechaEntrega)->format('Y-m-d');
                });

            foreach ($customers as $customer) {
                foreach ($orders as $order) {
                    for ($i = 0; $i < sizeof($order); $i++) {
                        if ($customer->idCliente == $order[$i]->delivery->idCliente) {
                            $dataObj = (object)array();
                            $dataObj->customer = $customer->nomEmpresa;
                            $dataObj->fecha = Carbon::parse($order[$i]->fechaEntrega)->format('Y-m-d');
                            $initDateTime = new Carbon(date('Y-m-d', strtotime($dataObj->fecha)) . ' 00:00:00');
                            $finDateTime = new Carbon(date('Y-m-d', strtotime($dataObj->fecha)) . ' 23:59:59');
                            $dataObj->orders = DetalleDelivery::whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                                ->whereIn('idEstado', [44, 46, 47])
                                ->whereHas('delivery', function ($q) use ($customer) {
                                    $q->where('idCliente', $customer->idCliente);
                                })->count();
                            $exist = 0;
                            foreach ($outputData as $output) {
                                if ($dataObj->fecha == $output->fecha && $dataObj->customer == $output->customer) {
                                    $exist++;
                                }
                            }

                            if ($exist == 0) {
                                array_push($outputData, $dataObj);
                            }
                        }
                    }
                }
            }

            $dates = array();
            foreach ($outputData as $my_object) {
                $dates[] = $my_object->fecha; //any object field
            }

            array_multisort($dates, SORT_ASC, $outputData);


            return response()->json(
                [
                    'error' => 0,
                    'data' => array(
                        'ordersReport' => $outputData,
                        //'totalOrders' => $totalOrders,
                        'ordersByCategory' => $ordersByCatArray,
                        'totalSurcharges' => $totalSurcharges,
                        'totalExtraCharges' => $totalExtraCharges,
                        'totalCosts' => $totalCosts,
                        'ordersInRange' => $ordersInRange,
                        //'orders' => $orders
                    )
                ],
                200
            );
        } catch (Exception $ex) {
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

    //Report Orders By Driver

    public function reportConsolidatedOrdersByDriver(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.driverId' => 'required',
            'form.initDate' => 'required',
            'form.finDate' => 'required'
        ]);


        try {
            $form = $request->form;
            $driver = $form['driverId'];

            $initDateTime = new Carbon(date('Y-m-d', strtotime($form['initDate'])) . ' 00:00:00');
            $finDateTime = new Carbon(date('Y-m-d', strtotime($form['finDate'])) . ' 23:59:59');

            $results = [];

            $dates = [];
            array_push($dates, $form['initDate']);
            $diff = $finDateTime->diff($initDateTime)->days + 1;
            for ($i = 1; $i < $diff; $i++) {
                $iniDate = new Carbon(date('Y-m-d', strtotime($form['initDate'])) . ' 00:00:00');
                array_push($dates, Carbon::parse($iniDate->addDays($i))->format('Y-m-d'));
            }

            if ($driver == -1) {

                $drivers = User::where(['isActivo' => 1, 'idPerfil' => 7])
                    ->orderBy('nomUsuario', 'ASC')
                    ->get(['nomUsuario', 'idUsuario']);

                foreach ($drivers as $driver) {
                    $outputData = $this->modifyConsolidatedOrderByCategory($dates, $driver->idUsuario);

                    $objToAdd = [];
                    array_push($objToAdd, $driver->nomUsuario);
                    $finTotalOrdes = 0;
                    $finTotalTime = 0;
                    $finTotalMoney = 0;
                    foreach ($outputData as $output) {
                        array_push($objToAdd, $output->totalOrders);
                        array_push($objToAdd, $output->tiempototal);
                        $finTotalOrdes += $output->totalOrders;
                        $finTotalTime += $output->tiempototal;
                        $finTotalMoney += $output->totalMoney;
                    }
                    array_push($objToAdd, $finTotalOrdes);
                    array_push($objToAdd, $finTotalTime);
                    array_push($objToAdd, $finTotalMoney);

                    array_push($results, $objToAdd);
                }
            } else {
                $driverDetails = User::where('idUsuario', $driver)->get()->first();
                $outputData = $this->modifyConsolidatedOrderByCategory($dates, $driver);
                if (sizeof($outputData) > 0) {
                    $objToAdd = [];
                    array_push($objToAdd, $driverDetails->nomUsuario);
                    $finTotalOrdes = 0;
                    $finTotalTime = 0;
                    $finTotalMoney = 0;
                    foreach ($outputData as $output) {
                        array_push($objToAdd, $output->totalOrders);
                        array_push($objToAdd, $output->tiempototal);
                        $finTotalOrdes += $output->totalOrders;
                        $finTotalTime += $output->tiempototal;
                        $finTotalMoney += $output->totalMoney;
                    }
                    array_push($objToAdd, $finTotalOrdes);
                    array_push($objToAdd, $finTotalTime);
                    array_push($objToAdd, $finTotalMoney);

                    array_push($results, $objToAdd);
                }

            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => $results,
                    'dates' => $dates
                ],
                200
            );
        } catch (Exception $ex) {
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

    private function modifyConsolidatedOrderByCategory($dates, $driver)
    {
        $outputData = [];
        foreach ($dates as $date) {
            $orders = DetalleDelivery::with(['delivery'])
                ->whereIn('idEstado', [44, 46, 47])
                ->whereDate('fechaEntrega', $date)
                ->where('idConductor', $driver)
                ->orWhere('idAuxiliar', $driver)
                ->whereDate('fechaEntrega', $date)
                ->whereIn('idEstado', [44, 46, 47])
                ->orderBy('fechaEntrega')
                ->get();
            $dataObj = (object)array();
            $dataObj->fecha = $date;
            $moto = 0;
            $turismo = 0;
            $pickup = 0;
            $panel = 0;
            $pickupAuxiliar = 0;
            $panelAuxiliar = 0;
            $transTurism = 0;
            $camion11 = 0;
            $nextDay = 0;
            $motoTime = 0;
            $motoMoney = 0;
            $motoOver20kms = 0;
            $turismoTime = 0;
            $turismoMoney = 0;
            $turismoOver20kms = 0;
            $pickupTime = 0;
            $pickupMoney = 0;
            $pickupOver20kms = 0;
            $panelTime = 0;
            $panelMoney = 0;
            $panelOver20kms = 0;
            $pickupAuxiliarTime = 0;
            $pickupAuxiliarMoney = 0;
            $pickupAuxiliarOver20kms = 0;
            $panelAuxiliarTime = 0;
            $panelAuxiliarMoney = 0;
            $panelAuxiliarOver20kms = 0;
            $transTurismTime = 0;
            $transTurismMoney = 0;
            $transTurismOver20kms = 0;
            $camion11Time = 0;
            $camion11Money = 0;
            $camion11Over20kms = 0;
            $nextDayTime = 0;
            $nextDayMoney = 0;
            $nextDayOver20kms = 0;


            if (sizeof($orders) > 0) {
                foreach ($orders as $order) {
                    $tCounterMoto = 0;
                    $mCounterMoto = 0;
                    $o20CounterMoto = 0;
                    $tCounterTurismo = 0;
                    $mCounterTurismo = 0;
                    $o20CounterTurismo = 0;
                    $tCounterPickup = 0;
                    $mCounterPickup = 0;
                    $o20CounterPickup = 0;
                    $tCounterPanel = 0;
                    $mCounterPanel = 0;
                    $o20CounterPanel = 0;
                    $tCounterPickupAuxiliar = 0;
                    $mCounterPickupAuxiliar = 0;
                    $o20CounterPickupAuxiliar = 0;
                    $tCounterPanelAuxiliar = 0;
                    $mCounterPanelAuxiliar = 0;
                    $o20CounterPanelAuxiliar = 0;
                    $tCounterTransTurism = 0;
                    $mCounterTransTurism = 0;
                    $o20CounterTransTurism = 0;
                    $tCounterCamion11 = 0;
                    $mCounterCamion11 = 0;
                    $o20CounterCamion11 = 0;
                    $tCounternextDay = 0;
                    $mCounternextDay = 0;
                    $o20CounternextDay = 0;

                    switch ($order->delivery->idCategoria) {
                        case 6:
                            $moto++;

                            if ($order->tiempo != null) {
                                if (strpos($order->tiempo, 'hour') || strpos($order->tiempo, 'h')) {
                                    $stime = explode(' ', $order->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterMoto = $o20CounterMoto + intval($time);
                                    }
                                    $order->tiempo = 20 + intval($time);
                                    $tCounterMoto = $tCounterMoto + intval($order->tiempo);
                                } else {
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterMoto = $o20CounterMoto + intval($order->tiempo);
                                    }
                                    $order->tiempo = 20 + intval($order->tiempo);
                                    $tCounterMoto = $tCounterMoto + intval($order->tiempo);
                                }
                            }
                            $mCounterMoto = $mCounterMoto + $order->efectivoRecibido;

                            $motoTime += $tCounterMoto;
                            $motoMoney += $mCounterMoto;
                            $motoOver20kms += $o20CounterMoto;
                            break;
                        case 1:
                            $turismo++;

                            if ($order->tiempo != null) {
                                if (strpos($order->tiempo, 'hour') || strpos($order->tiempo, 'h')) {
                                    $stime = explode(" ", $order->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterTurismo = $o20CounterTurismo + intval($time);
                                    }
                                    $order->tiempo = 20 + intval($time);
                                    $tCounterTurismo = $tCounterTurismo + intval($order->tiempo);
                                } else {
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterTurismo = $o20CounterTurismo + intval($order->tiempo);
                                    }
                                    $order->tiempo = 20 + intval($order->tiempo);
                                    $tCounterTurismo = $tCounterTurismo + intval($order->tiempo);
                                }
                            }
                            $mCounterTurismo = $mCounterTurismo + $order->efectivoRecibido;

                            $turismoTime += $tCounterTurismo;
                            $turismoMoney += $mCounterTurismo;
                            $turismoOver20kms += $o20CounterTurismo;
                            break;
                        case 13:
                            $turismo++;

                            if ($order->tiempo != null) {
                                if (strpos($order->tiempo, 'hour') || strpos($order->tiempo, 'h')) {
                                    $stime = explode(" ", $order->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterTurismo = $o20CounterTurismo + intval($time);
                                    }
                                    $order->tiempo = 20 + intval($time);
                                    $tCounterTurismo = $tCounterTurismo + intval($order->tiempo);
                                } else {
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterTurismo = $o20CounterTurismo + intval($order->tiempo);
                                    }
                                    $order->tiempo = 20 + intval($order->tiempo);
                                    $tCounterTurismo = $tCounterTurismo + intval($order->tiempo);
                                }
                            }
                            $mCounterTurismo = $mCounterTurismo + $order->efectivoRecibido;

                            $turismoTime += $tCounterTurismo;
                            $turismoMoney += $mCounterTurismo;
                            $turismoOver20kms += $o20CounterTurismo;
                            break;
                        case 2:
                            $pickup++;

                            if ($order->tiempo != null) {
                                if (strpos($order->tiempo, 'hour') || strpos($order->tiempo, 'h')) {
                                    $stime = explode(' ', $order->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPickup = $o20CounterPickup + intval($time);
                                    }
                                    $order->tiempo = 40 + intval($time);
                                    $tCounterPickup = $tCounterPickup + intval($order->tiempo);
                                } else {
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPickup = $o20CounterPickup + intval($order->tiempo);
                                    }
                                    $order->tiempo = 40 + intval($order->tiempo);
                                    $tCounterPickup = $tCounterPickup + intval($order->tiempo);
                                }
                            }
                            $mCounterPickup = $mCounterPickup + $order->efectivoRecibido;

                            $pickupTime += $tCounterPickup;
                            $pickupMoney += $mCounterPickup;
                            $pickupOver20kms += $o20CounterPickup;
                            break;
                        case 3:
                            $panel++;

                            if ($order->tiempo != null) {
                                if (strpos($order->tiempo, 'hour') || strpos($order->tiempo, 'h')) {
                                    $stime = explode(' ', $order->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPanel = $o20CounterPanel + intval($time);
                                    }
                                    $order->tiempo = 40 + intval($time);
                                    $tCounterPanel = $tCounterPanel + intval($order->tiempo);
                                } else {
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPanel = $o20CounterPanel + intval($order->tiempo);
                                    }
                                    $order->tiempo = 40 + intval($order->tiempo);
                                    $tCounterPanel = $tCounterPanel + intval($order->tiempo);
                                }
                            }
                            $mCounterPanel = $mCounterPanel + $order->efectivoRecibido;

                            $panelTime += $tCounterPanel;
                            $panelMoney += $mCounterPanel;
                            $panelOver20kms += $o20CounterPanel;
                            break;
                        case 4:
                            $pickupAuxiliar++;

                            if ($order->tiempo != null && $order->idAuxiliar != $driver) {
                                if (strpos($order->tiempo, 'hour') || strpos($order->tiempo, 'h')) {
                                    $stime = explode(' ', $order->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                    }
                                    $order->tiempo = 40 + intval($time);
                                    $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($order->tiempo);
                                } else {
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($order->tiempo);
                                    }
                                    $order->tiempo = 40 + intval($order->tiempo);
                                    $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($order->tiempo);
                                }
                                $mCounterPickupAuxiliar = $mCounterPickupAuxiliar + $order->efectivoRecibido;
                            } else if ($order->idAuxiliar == $driver) {
                                if (strpos($order->tiempo, 'hour') || strpos($order->tiempo, 'h')) {
                                    $stime = explode(' ', $order->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                    }
                                } else {
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($order->tiempo);
                                    }
                                }
                            }

                            $pickupAuxiliarTime += $tCounterPickupAuxiliar;
                            $pickupAuxiliarMoney += $mCounterPickupAuxiliar;
                            $pickupAuxiliarOver20kms += $o20CounterPickupAuxiliar;
                            break;
                        case 11:
                            $pickupAuxiliar++;

                            if ($order->tiempo != null && $order->idAuxiliar != $driver) {
                                if (strpos($order->tiempo, 'hour') || strpos($order->tiempo, 'h')) {
                                    $stime = explode(' ', $order->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                    }
                                    $order->tiempo = 40 + intval($time);
                                    $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($order->tiempo);
                                } else {
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($order->tiempo);
                                    }
                                    $order->tiempo = 40 + intval($order->tiempo);
                                    $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($order->tiempo);
                                }
                                $mCounterPickupAuxiliar = $mCounterPickupAuxiliar + $order->efectivoRecibido;
                            } else if ($order->idAuxiliar == $driver) {
                                if (strpos($order->tiempo, 'hour') || strpos($order->tiempo, 'h')) {
                                    $stime = explode(' ', $order->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                    }
                                } else {
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($order->tiempo);
                                    }
                                }
                            }

                            $pickupAuxiliarTime += $tCounterPickupAuxiliar;
                            $pickupAuxiliarMoney += $mCounterPickupAuxiliar;
                            $pickupAuxiliarOver20kms += $o20CounterPickupAuxiliar;
                            break;
                        case 12:
                            $pickupAuxiliar++;

                            if ($order->tiempo != null && $order->idAuxiliar != $driver) {
                                if (strpos($order->tiempo, 'hour') || strpos($order->tiempo, 'h')) {
                                    $stime = explode(' ', $order->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                    }
                                    $order->tiempo = 40 + intval($time);
                                    $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($order->tiempo);
                                } else {
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($order->tiempo);
                                    }
                                    $order->tiempo = 40 + intval($order->tiempo);
                                    $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($order->tiempo);
                                }
                                $mCounterPickupAuxiliar = $mCounterPickupAuxiliar + $order->efectivoRecibido;
                            } else if ($order->idAuxiliar == $driver) {
                                if (strpos($order->tiempo, 'hour') || strpos($order->tiempo, 'h')) {
                                    $stime = explode(' ', $order->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                    }
                                } else {
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($order->tiempo);
                                    }
                                }
                            }

                            $pickupAuxiliarTime += $tCounterPickupAuxiliar;
                            $pickupAuxiliarMoney += $mCounterPickupAuxiliar;
                            $pickupAuxiliarOver20kms += $o20CounterPickupAuxiliar;
                            break;
                        case 5:
                            $panelAuxiliar++;

                            if ($order->tiempo != null && $order->idAuxiliar != $driver) {
                                if (strpos($order->tiempo, 'hour') || strpos($order->tiempo, 'h')) {
                                    $stime = explode(' ', $order->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPanelAuxiliar = $o20CounterPanelAuxiliar + intval($time);
                                    }
                                    $order->tiempo = 40 + intval($time);
                                    $tCounterPanelAuxiliar = $tCounterPanelAuxiliar + intval($order->tiempo);
                                } else {
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPanelAuxiliar = $o20CounterPanelAuxiliar + intval($order->tiempo);
                                    }
                                    $order->tiempo = 40 + intval($order->tiempo);
                                    $tCounterPanelAuxiliar = $tCounterPanelAuxiliar + intval($order->tiempo);
                                }
                                $mCounterPanelAuxiliar = $mCounterPanelAuxiliar + $order->efectivoRecibido;
                            } else if ($order->idAuxiliar == $driver) {
                                if (strpos($order->tiempo, 'hour') || strpos($order->tiempo, 'h')) {
                                    $stime = explode(' ', $order->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPanelAuxiliar = $o20CounterPanelAuxiliar + intval($time);
                                    }
                                } else {
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterPanelAuxiliar = $o20CounterPanelAuxiliar + intval($order->tiempo);
                                    }
                                }
                            }

                            $panelAuxiliarTime += $tCounterPanelAuxiliar;
                            $panelAuxiliarMoney += $mCounterPanelAuxiliar;
                            $panelAuxiliarOver20kms += $o20CounterPanelAuxiliar;
                            break;
                        case 7:
                            $transTurism++;

                            if ($order->tiempo != null) {
                                if (strpos($order->tiempo, 'hour') || strpos($order->tiempo, 'h')) {
                                    $stime = explode(' ', $order->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterTransTurism = $o20CounterTransTurism + intval($time);
                                    }
                                    $order->tiempo = 20 + intval($time);
                                    $tCounterTransTurism = $tCounterTransTurism + intval($order->tiempo);
                                } else {
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterTransTurism = $o20CounterTransTurism + intval($order->tiempo);
                                    }
                                    $order->tiempo = 20 + intval($order->tiempo);
                                    $tCounterTransTurism = $tCounterTransTurism + intval($order->tiempo);
                                }
                            }
                            $mCounterTransTurism = $mCounterTransTurism + $order->efectivoRecibido;

                            $transTurismTime += $tCounterTransTurism;
                            $transTurismMoney += $mCounterTransTurism;
                            $transTurismOver20kms += $o20CounterTransTurism;
                            break;
                        case 8:
                            $camion11++;

                            if ($order->tiempo != null && $order->idAuxiliar != $driver) {
                                if (strpos($order->tiempo, 'hour') || strpos($order->tiempo, 'h')) {
                                    $stime = explode(' ', $order->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterCamion11 = $o20CounterCamion11 + intval($time);
                                    }
                                    $order->tiempo = 70 + intval($time);
                                    $tCounterCamion11 = $tCounterCamion11 + intval($order->tiempo);
                                } else {
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterCamion11 = $o20CounterCamion11 + intval($order->tiempo);
                                    }
                                    $order->tiempo = 70 + intval($order->tiempo);
                                    $tCounterCamion11 = $tCounterCamion11 + intval($order->tiempo);
                                }
                                $mCounterCamion11 = $mCounterCamion11 + $order->efectivoRecibido;
                            } else if ($order->idAuxiliar == $driver) {
                                if (strpos($order->tiempo, 'hour') || strpos($order->tiempo, 'h')) {
                                    $stime = explode(' ', $order->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterCamion11 = $o20CounterCamion11 + intval($time);
                                    }
                                } else {
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounterCamion11 = $o20CounterCamion11 + intval($order->tiempo);
                                    }
                                }
                            }

                            $camion11Time += $tCounterCamion11;
                            $camion11Money += $mCounterCamion11;
                            $camion11Over20kms += $o20CounterCamion11;
                            break;

                        case 10:
                            $nextDay++;

                            if ($order->tiempo != null) {
                                if (strpos($order->tiempo, 'hour') || strpos($order->tiempo, 'h')) {
                                    $stime = explode(" ", $order->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounternextDay = $o20CounternextDay + intval($time);
                                    }
                                    $order->tiempo = 20 + intval($time);
                                    $tCounternextDay = $tCounternextDay + intval($order->tiempo);
                                } else {
                                    if (floatval($order->distancia) > 20) {
                                        $o20CounternextDay = $o20CounternextDay + intval($order->tiempo);
                                    }
                                    $order->tiempo = 20 + intval($order->tiempo);
                                    $tCounternextDay = $tCounternextDay + intval($order->tiempo);
                                }
                            }
                            $mCounternextDay = $mCounternextDay + $order->efectivoRecibido;

                            $nextDayTime += $tCounternextDay;
                            $nextDayMoney += $mCounternextDay;
                            $nextDayOver20kms += $o20CounternextDay;
                            break;

                    }

                }
                $dataObj->totalOrders = $moto + $turismo + $pickup + $panel + $pickupAuxiliar + $panelAuxiliar + $transTurism + $camion11 + $nextDay;
                $dataObj->totalTime = $motoTime + $turismoTime + $pickupTime + $panelTime + $pickupAuxiliarTime + $panelAuxiliarTime + $transTurismTime + $camion11Time + $nextDayTime;
                $dataObj->totalMoney = $motoMoney + $turismoMoney + $pickupMoney + $panelMoney + $pickupAuxiliarMoney + $panelAuxiliarMoney + $transTurismMoney + $camion11Money + $nextDayMoney;
                $dataObj->totalOver20kms = $motoOver20kms + $turismoOver20kms + $pickupOver20kms + $panelOver20kms + $pickupAuxiliarOver20kms + $panelAuxiliarOver20kms + $transTurismOver20kms + $camion11Over20kms + $nextDayOver20kms;

                $auxTime = DetalleDelivery::with('delivery')->whereIn('idEstado', [44, 46, 47])
                    ->where([
                        'idAuxiliar' => $driver,
                    ])
                    ->whereHas('delivery', function ($q) {
                        $q->whereIn('idCategoria', [4, 5, 8, 11, 12]);
                    })
                    ->whereDate('fechaEntrega', $date)
                    ->get();

                $auxCounter = 0;

                foreach ($auxTime as $aux) {
                    if ($aux->tiempo != null) {
                        if ($aux->delivery->idCategoria == 8) {
                            if (strpos($aux->tiempo, 'hour')) {
                                $stime = explode(' ', $aux->tiempo);
                                $time = intval($stime[0]) * 60 + intval($stime[2]);

                                $aux->tiempo = 60 + intval($time);
                                $auxCounter = $auxCounter + intval($aux->tiempo);
                            } else {
                                $aux->tiempo = 60 + intval($aux->tiempo);
                                $auxCounter = $auxCounter + intval($aux->tiempo);
                            }
                        } else {
                            if (strpos($aux->tiempo, 'hour')) {
                                $stime = explode(' ', $aux->tiempo);
                                $time = intval($stime[0]) * 60 + intval($stime[2]);

                                $aux->tiempo = (40 + intval($time)) - 10;
                                $auxCounter = $auxCounter + intval($aux->tiempo);
                            } else {
                                $aux->tiempo = (40 + intval($aux->tiempo)) - 10;
                                $auxCounter = $auxCounter + intval($aux->tiempo);
                            }
                        }

                    }
                }
                $dataObj->totalAuxTime = $auxCounter;

                $extTime = DetalleDelivery::with('extraCharges')
                    ->whereIn('idEstado', [44, 46, 47])
                    ->where([
                        'idConductor' => $driver,
                    ])
                    ->whereDate('fechaEntrega', $date)
                    ->orWhere('idAuxiliar', $driver)
                    ->whereIn('idEstado', [44, 46, 47])
                    ->whereDate('fechaEntrega', $date)
                    ->get();

                $extCounter = 0;

                foreach ($extTime as $ext) {
                    if (sizeof($ext->extraCharges) > 0) {
                        foreach ($ext->extraCharges as $exCharge) {
                            $extCounter += $exCharge->option->tiempo;
                        }
                    }
                }
                $dataObj->totalExtraTime = $extCounter;

                $dataObj->tiempototal = $dataObj->totalTime + $dataObj->totalOver20kms + $dataObj->totalAuxTime + $dataObj->totalExtraTime;

            } else {
                $dataObj->totalOrders = 0;
                $dataObj->totalTime = 0;
                $dataObj->totalMoney = 0;
                $dataObj->totalOver20kms = 0;
                $dataObj->totalAuxTime = 0;
                $dataObj->totalExtraTime = 0;
                $dataObj->tiempototal = 0;
            }
            array_push($outputData, $dataObj);

        }

        return $outputData;
    }

    /*********************************
     * FUNCIONES DE CLIENTES
     ********************************/


    /****
     * CREATE DELIVERIES FUNCTIONS
     ****/

    public function createDelivery(Request $request)
    {
        $request->validate([
            'deliveryForm' => 'required',
            'deliveryForm.nomCliente' => 'required',
            'deliveryForm.numIdentificacion' => 'required',
            'deliveryForm.numCelular' => 'required',
            'deliveryForm.fecha' => 'required',
            'deliveryForm.hora' => 'required',
            'deliveryForm.dirRecogida' => 'required',
            'deliveryForm.email' => 'required',
            'deliveryForm.idCategoria' => 'required',
            'orders' => 'required',
            'pago' => 'required'
        ]);
        $hDelivery = $request->deliveryForm;
        $deliveryOrders = $request->orders;
        $pago = $request->pago;

        try {
            $nDelivery = new Delivery();
            $nDelivery->nomCliente = $hDelivery['nomCliente'];
            $nDelivery->numIdentificacion = $hDelivery['numIdentificacion'];
            $nDelivery->numCelular = $hDelivery['numCelular'];
            $date = date('Y-m-d', strtotime($hDelivery['fecha']));
            $time = $hDelivery['hora'];
            $datetime = $date . ' ' . $time;
            $nDelivery->fechaReserva = new Carbon($datetime);
            $nDelivery->dirRecogida = $hDelivery['dirRecogida'];
            $nDelivery->email = $hDelivery['email'];
            $nDelivery->idCategoria = $hDelivery['idCategoria'];
            $nDelivery->idEstado = 34;
            $nDelivery->tarifaBase = $pago['baseRate'];
            $nDelivery->recargos = $pago['recargos'];
            $nDelivery->total = $pago['total'];
            $nDelivery->instrucciones = $hDelivery['instrucciones'];
            $nDelivery->fechaRegistro = Carbon::now();
            $nDelivery->save();

            $lastId = Delivery::query()->max('idDelivery');

            foreach ($deliveryOrders as $detalle) {
                $nDetalle = new DetalleDelivery();
                $nDetalle->idDelivery = $lastId;
                $nDetalle->nFactura = $detalle['nFactura'];
                $nDetalle->nomDestinatario = $detalle['nomDestinatario'];
                $nDetalle->numCel = $detalle['numCel'];
                $nDetalle->direccion = $detalle['direccion'];
                $nDetalle->distancia = $detalle['distancia'];
                $nDetalle->tarifaBase = $detalle['tarifaBase'];
                $nDetalle->recargo = $detalle['recargo'];
                $nDetalle->cTotal = $detalle['cTotal'];
                $nDetalle->instrucciones = $detalle['instrucciones'];
                $nDetalle->save();
            }

            $receivers = $hDelivery['email'];
            $this->sendmail($receivers, $lastId);

            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Solicitud de Delivery enviada correctamente.
                    Recibirás un email con los detalles de tu reserva. Nos pondremos en contacto contigo.',
                    'nDelivery' => $lastId
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array('context' => $ex->getTrace()));
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al cargar cancelar la solicitud'
                ],
                500
            );
        }
    }

    public function characterReplace($string)
    {
        $newString = str_replace(array('#', '?', '¿'), '', $string);
        return $newString;
    }


    public function createCustomerDelivery(Request $request)
    {
        $request->validate([
            'deliveryForm' => 'required',
            'deliveryForm.dirRecogida' => 'required',
            'deliveryForm.idCategoria' => 'required',
            'deliveryForm.coordsOrigen' => 'required',
            'deliveryForm.fecha' => 'required',
            'deliveryForm.hora' => 'required',
            'deliveryForm.idTarifa' => 'required',
            'orders' => 'required|array|min:1',
            'pago' => 'required'
        ]);

        $customer = Auth::user()->idCliente;
        if (Auth::user()->idPerfil !== 1 && Auth::user()->idPerfil !== 9) {
            $customer = $request->idCliente;
        }

        if ($request->idCustomer == null) {
            $customer = Auth::user()->idCliente;
        } else {
            $customer = $request->idCustomer;
        }

        $hDelivery = $request->deliveryForm;
        $deliveryOrders = $request->orders;
        $pago = $request->pago;

        if ($request->deliveryForm["idTarifa"]["idTipoTarifa"] != 2) {
            $deliveryDayCode = Carbon::create(date('Y-m-d', strtotime($hDelivery['fecha'])))->dayOfWeek;

            $todaySchedule = Schedule::where('cod', $deliveryDayCode)->where('idTarifaDelivery', null)->get()->first();

            if (
                date('H:i', strtotime($hDelivery['hora'])) < date('H:i', strtotime($todaySchedule->inicio)) ||
                date('H:i', strtotime($hDelivery['hora'])) > date('H:i', strtotime($todaySchedule->final))
            ) {
                if (Auth::user()->idPerfil == 9 || Auth::user()->idPerfil == 1) {

                } else {
                    return response()->json(
                        [
                            'error' => 1,
                            'message' => 'Lo sentimos, la hora de reservación está fuera del horario.
                        Puede que el horario haya cambiado recientemente.
                        Por favor recargue la página por lo menos 2 veces para verificar el cambio.'
                        ],
                        500
                    );
                }

            }
        }

        if (sizeof($deliveryOrders) > 0) {
            try {
                $customerDetails = DeliveryClient::where('idCliente', $customer)
                    ->get()
                    ->first();

                $nDelivery = new Delivery();
                $nDelivery->nomCliente = $customerDetails->nomEmpresa;
                $nDelivery->numIdentificacion = $customerDetails->numIdentificacion;
                $nDelivery->numCelular = $customerDetails->numTelefono;

                $date = date('Y-m-d', strtotime($hDelivery['fecha']));
                $time = date('H:i', strtotime($hDelivery['hora']));

                $datetime = $date . ' ' . $time;
                $nDelivery->fechaReserva = new Carbon($datetime);
                $nDelivery->dirRecogida = $this->characterReplace($hDelivery['dirRecogida']);
                $nDelivery->email = $customerDetails->email;
                $nDelivery->idCategoria = $hDelivery['idCategoria'];
                $nDelivery->idEstado = 34;
                $nDelivery->tarifaBase = $pago['baseRate'];
                $nDelivery->recargos = $pago['recargos'];
                $nDelivery->cargosExtra = $pago['cargosExtra'];
                $nDelivery->total = $pago['total'];
                $nDelivery->idCliente = $customerDetails->idCliente;
                $nDelivery->coordsOrigen = $hDelivery['coordsOrigen'];
                $nDelivery->instrucciones = $this->characterReplace($hDelivery['instrucciones']);
                $nDelivery->idTarifaDelivery = $request->deliveryForm["idTarifa"]["idTarifaDelivery"];
                if ($request->deliveryForm["idTarifa"]["idTipoTarifa"] == 2) {
                    $nDelivery->isConsolidada = true;
                }
                if (isset($request->deliveryForm["idEtiqueta"])) {
                    $nDelivery->idEtiqueta = $request->deliveryForm["idEtiqueta"];
                }

                if (isset($request->deliveryForm["distancia"]) && $request->deliveryForm["distancia"] != 0) {
                    $nDelivery->isRuteo = true;
                    $nDelivery->distTotal = $hDelivery['distancia'];
                }

                if (strpos($this->characterReplace($hDelivery['dirRecogida']), 'Tegucigalpa')) {
                    $nDelivery->ciudad = 'Tegucigalpa';
                } else if (strpos($this->characterReplace($hDelivery['dirRecogida']), 'San Pedro Sula')) {
                    $nDelivery->ciudad = 'San Pedro Sula';
                }
                if ($request->idCustomer != null) {
                    $nDelivery->regAdmin = true;
                    $nDelivery->registradoPor = Auth::user()->idUsuario;
                }

                $nDelivery->fechaRegistro = Carbon::now();
                $nDelivery->save();

                $lastId = Delivery::query()->max('idDelivery');
                $itemDet = ItemDetail::where('idTarifaDelivery', $request->deliveryForm["idTarifa"]["idTarifaDelivery"])
                    ->get()->first();

                foreach ($deliveryOrders as $detalle) {
                    $nDetalle = new DetalleDelivery();
                    $nDetalle->idDelivery = $lastId;
                    $nDetalle->nFactura = $this->characterReplace($detalle['nFactura']);
                    $nDetalle->nomDestinatario = $this->characterReplace($detalle['nomDestinatario']);
                    $nDetalle->numCel = $detalle['numCel'];
                    $nDetalle->direccion = $this->characterReplace($detalle['direccion']);
                    $nDetalle->distancia = $detalle['distancia'];
                    $nDetalle->tiempo = $detalle['tiempo'];
                    $nDetalle->tarifaBase = $detalle['tarifaBase'];
                    $nDetalle->recargo = $detalle['recargo'];
                    $nDetalle->cTotal = $detalle['cTotal'];
                    $nDetalle->cargosExtra = $detalle['cargosExtra'];
                    $nDetalle->tomarFoto = true;
                    $nDetalle->instrucciones = $this->characterReplace($detalle['instrucciones']);
                    $nDetalle->coordsDestino = $detalle['coordsDestino'];
                    $nDetalle->idRecargo = $detalle['idRecargo'];
                    $nDetalle->save();

                    $lastOrderId = DetalleDelivery::query()->max('idDetalle');
                    if($hDelivery->prioridad == 1){
                        $priorityId = 16;
                        $nECOrder = new ExtraChargesOrders();
                        $nECOrder->idDetalle = $nDetalle->idDetalle;
                        $nECOrder->idCargoExtra = $priorityId;
                        $nECOrder->save();

                        $extrachargeItemDet = ItemDetail::where('idCargoExtra', $priorityId)
                            ->get()->first();

                        if ($extrachargeItemDet != null) {
                            $nSurValFact = new OrderExtrachargeFactValues();
                            $nSurValFact->idDetalle = $lastOrderId;
                            $nSurValFact->tYK = $extrachargeItemDet->tYK;
                            $nSurValFact->cobVehiculo = $extrachargeItemDet->cobVehiculo;
                            $nSurValFact->servChofer = $extrachargeItemDet->servChofer;
                            $nSurValFact->recCombustible = $extrachargeItemDet->recCombustible;
                            $nSurValFact->cobTransporte = $extrachargeItemDet->cobTransporte;
                            $nSurValFact->isv = $extrachargeItemDet->isv;
                            $nSurValFact->tasaTuris = $extrachargeItemDet->tasaTuris;
                            $nSurValFact->gastosReembolsables = $extrachargeItemDet->gastosReembolsables;
                            $nSurValFact->save();
                        }
                    }

                    if (isset($detalle['extras'])) {
                        foreach ($detalle['extras'] as $exCharge) {
                            $nECOrder = new ExtraChargesOrders();
                            $nECOrder->idDetalle = $nDetalle->idDetalle;
                            $nECOrder->idCargoExtra = $exCharge["idCargoExtra"];
                            $nECOrder->idDetalleOpcion = $exCharge["idDetalleOpcion"];
                            if (isset($exCharge["montoCobertura"])) {
                                $nECOrder->montoCobertura = $exCharge["montoCobertura"];
                            }
                            $nECOrder->save();

                            $extrachargeItemDet = ItemDetail::where('idDetalleOpcion', $exCharge["idDetalleOpcion"])
                                ->get()->first();
                            if ($extrachargeItemDet != null) {
                                $nSurValFact = new OrderExtrachargeFactValues();
                                $nSurValFact->idDetalle = $lastOrderId;
                                $nSurValFact->tYK = $extrachargeItemDet->tYK;
                                $nSurValFact->cobVehiculo = $extrachargeItemDet->cobVehiculo;
                                $nSurValFact->servChofer = $extrachargeItemDet->servChofer;
                                $nSurValFact->recCombustible = $extrachargeItemDet->recCombustible;
                                $nSurValFact->cobTransporte = $extrachargeItemDet->cobTransporte;
                                $nSurValFact->isv = $extrachargeItemDet->isv;
                                $nSurValFact->tasaTuris = $extrachargeItemDet->tasaTuris;
                                $nSurValFact->gastosReembolsables = $extrachargeItemDet->gastosReembolsables;
                                $nSurValFact->save();
                            }
                        }
                    }


                    if ($itemDet != null) {
                        $nValFact = new OrderFactValues();
                        $nValFact->idDetalle = $lastOrderId;
                        $nValFact->tYK = $itemDet->tYK;
                        $nValFact->cobVehiculo = $itemDet->cobVehiculo;
                        $nValFact->servChofer = $itemDet->servChofer;
                        $nValFact->recCombustible = $itemDet->recCombustible;
                        $nValFact->cobTransporte = $itemDet->cobTransporte;
                        $nValFact->isv = $itemDet->isv;
                        $nValFact->tasaTuris = $itemDet->tasaTuris;
                        $nValFact->gastosReembolsables = $itemDet->gastosReembolsables;
                        $nValFact->save();
                    }

                    if ($detalle['idRecargo'] != null) {
                        $surchargeItemDet = ItemDetail::where('idRecargo', $detalle['idRecargo'])
                            ->get()->first();
                        if ($surchargeItemDet != null) {
                            $nSurValFact = new OrderSurchargeFactValues();
                            $nSurValFact->idDetalle = $lastOrderId;
                            $nSurValFact->tYK = $surchargeItemDet->tYK;
                            $nSurValFact->cobVehiculo = $surchargeItemDet->cobVehiculo;
                            $nSurValFact->servChofer = $surchargeItemDet->servChofer;
                            $nSurValFact->recCombustible = $surchargeItemDet->recCombustible;
                            $nSurValFact->cobTransporte = $surchargeItemDet->cobTransporte;
                            $nSurValFact->isv = $surchargeItemDet->isv;
                            $nSurValFact->tasaTuris = $surchargeItemDet->tasaTuris;
                            $nSurValFact->gastosReembolsables = $surchargeItemDet->gastosReembolsables;
                            $nSurValFact->save();
                        }

                    } else {
                        $nSurValFact = new OrderSurchargeFactValues();
                        $nSurValFact->idDetalle = $lastOrderId;
                        $nSurValFact->tYK = 0.00;
                        $nSurValFact->cobVehiculo = 0.00;
                        $nSurValFact->servChofer = 0.00;
                        $nSurValFact->recCombustible = 0.00;
                        $nSurValFact->cobTransporte = 0.00;
                        $nSurValFact->isv = 0.00;
                        $nSurValFact->tasaTuris = 0.00;
                        $nSurValFact->gastosReembolsables = 0.00;
                        $nSurValFact->save();
                    }

                }

                if ($customerDetails->enviarNotificaciones) {
                    $receivers = $customerDetails->email;
                    $this->sendmail($receivers, $lastId);
                }

                return response()->json(
                    [
                        'error' => 0,
                        'message' => "Solicitud de Delivery enviada correctamente.
                        Recibirás un email con los detalles de tu reserva. Nos pondremos en contacto contigo. ",
                        'nDelivery' => $lastId
                    ],
                    200
                );
            } catch (Exception $ex) {
                Log::error($ex->getMessage(), array(
                    'context' => $ex->getTrace()
                ));
                return response()->json(
                    [
                        'error' => 1,
                        'message' => 'Lo sentimos, ha ocurrido un error al procesar tu solicitud. Por favor intenta de nuevo.'
                    ],
                    500
                );
            }
        } else {
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Lo sentimos, ha ocurrido un error al procesar tu solicitud. Por favor intenta de nuevo.'
                ],
                500
            );
        }
    }

    public function createTraslate(Request $request)
    {
        $request->validate(['data' => 'required']);
        $customer = Auth::user()->idCliente;
        try {

            $customerDetails = DeliveryClient::where('idCliente', $customer)
                ->get()
                ->first();

            $nDelivery = new Delivery();
            $nDelivery->nomCliente = $customerDetails->nomRepresentante;
            $nDelivery->numIdentificacion = $customerDetails->numIdentificacion;
            $nDelivery->numCelular = $customerDetails->numTelefono;
            $nDelivery->fechaReserva = Carbon::now()->addMinutes(30);
            $nDelivery->dirRecogida = $request->data['origin'];
            $nDelivery->email = $customerDetails->email;
            $nDelivery->idCategoria = $request->data['category']['idCategoria'];
            $nDelivery->idEstado = 34;
            $nDelivery->tarifaBase = $request->data['payment']['baseRate'];
            $nDelivery->recargos = $request->data['payment']['surcharges'];
            $nDelivery->cargosExtra = 0.00; //$request->data['extraCharges'];
            $nDelivery->total = $request->data['payment']['total'];
            $nDelivery->idCliente = $customerDetails->idCliente;
            $nDelivery->coordsOrigen = NULL; //$hDelivery['coordsOrigen'];
            $nDelivery->instrucciones = NULL; //$request->data['instrRecogida'];
            $nDelivery->fechaRegistro = Carbon::now();
            $nDelivery->save();

            $lastId = Delivery::query()->max('idDelivery');

            $nDetalle = new DetalleDelivery();
            $nDetalle->idDelivery = $lastId;
            $nDetalle->nFactura = 'Traslado de Personas'; //$detalle['nFactura'];
            $nDetalle->nomDestinatario = $customerDetails->nomRepresentante; //$detalle['nomDestinatario'];
            $nDetalle->numCel = $customerDetails->numTelefono;
            $nDetalle->direccion = $request->data['destination'];
            $nDetalle->distancia = $request->data['distance'] . ' km';
            $nDetalle->tiempo = $request->data['time'];
            $nDetalle->tarifaBase = $request->data['payment']['baseRate'];
            $nDetalle->recargo = $request->data['payment']['surcharges'];
            $nDetalle->cTotal = $request->data['payment']['total'];
            $nDetalle->cargosExtra = 0.00; //$detalle['cargosExtra'];
            $nDetalle->tomarFoto = true;
            $nDetalle->instrucciones = NULL; //$detalle['instrucciones'];
            $nDetalle->coordsDestino = NULL; //$detalle['coordsDestino'];
            $nDetalle->save();

            /*if (isset($detalle['extras'])) {
                foreach ($detalle['extras'] as $exCharge) {
                    $nECOrder = new ExtraChargesOrders();
                    $nECOrder->idDetalle = $nDetalle->idDetalle;
                    $nECOrder->idCargoExtra = $exCharge["idCargoExtra"];
                    $nECOrder->idDetalleOpcion = $exCharge["idDetalleOpcion"];
                    $nECOrder->save();
                }
            }*/

            $newTransaction = new DeliveryTransaction();
            $newTransaction->idDelivery = $lastId;
            $newTransaction->idCliente = $customer;
            $newTransaction->reasonCode = $request->data['transactionDetails']['reasonCode'];
            $newTransaction->reasonCodeDescription = $request->data['transactionDetails']['reasonCodeDescription'];
            $newTransaction->authCode = $request->data['transactionDetails']['authCode'];
            $newTransaction->orderNumber = $request->data['transactionDetails']['orderNumber'];
            $newTransaction->fechaRegistro = Carbon::now();
            $newTransaction->save();


            $receivers = $customerDetails->email;
            $this->sendmail($receivers, $lastId);

            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Solicitud de Traslado enviada correctamente.
                        Recibirás un email con los detalles de tu reserva.',
                    'nDelivery' => $lastId
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array(
                'User' => Auth::user()->nomUsuario,
                'context' => $ex->getTrace()
            ));
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Lo sentimos, ha ocurrido un error al procesar tu solicitud. Por favor intenta de nuevo.'
                ],
                500
            );
        }
    }

    public function createDeliveryFromApp(Request $request)
    {
        $request->validate(['data' => 'required']);
        $customer = Auth::user()->idCliente;
        $detail = $request->data['orderDetail'];
        try {
            $customerDetails = DeliveryClient::where('idCliente', $customer)
                ->get()
                ->first();

            $nDelivery = new Delivery();
            $nDelivery->nomCliente = $customerDetails->nomRepresentante;
            $nDelivery->numIdentificacion = $customerDetails->numIdentificacion;
            $nDelivery->numCelular = $customerDetails->numTelefono;
            if (isset($detail['fechaReserva'])) {
                $nDelivery['fechaReserva'] = $detail['fechaReserva'];
            } else {
                $nDelivery->fechaReserva = Carbon::now()->addMinutes(30);
            }

            $nDelivery->dirRecogida = $request->data['origin'];
            $nDelivery->email = $customerDetails->email;
            $nDelivery->idCategoria = $request->data['category']['idCategoria'];
            $nDelivery->idEstado = 34;
            $nDelivery->tarifaBase = $request->data['payment']['baseRate'];
            $nDelivery->recargos = $request->data['payment']['surcharges'];
            $nDelivery->cargosExtra = $request->data['payment']['extraCharges'];
            $nDelivery->total = $request->data['payment']['total'];
            $nDelivery->idCliente = $customerDetails->idCliente;
            $nDelivery->coordsOrigen = NULL; //$hDelivery['coordsOrigen'];
            $nDelivery->instrucciones = $detail['instRecogida']; //$request->data['instrRecogida'];
            $nDelivery->fechaRegistro = Carbon::now();
            $nDelivery->save();

            $lastId = Delivery::query()->max('idDelivery');

            $nDetalle = new DetalleDelivery();
            $nDetalle->idDelivery = $lastId;
            $nDetalle->nFactura = $detail['detalleEnvio'];
            $nDetalle->nomDestinatario = $detail['nomDestinatario'];
            $nDetalle->numCel = $detail['numDestinatario'];
            $nDetalle->direccion = $request->data['destination'];
            $nDetalle->distancia = $request->data['distance'] . ' km';
            $nDetalle->tiempo = $request->data['time'];
            $nDetalle->tarifaBase = $request->data['payment']['baseRate'];
            $nDetalle->recargo = $request->data['payment']['surcharges'];
            $nDetalle->cTotal = $request->data['payment']['total'];
            if (isset($request->data['payment']['extraCharges'])) {
                $nDetalle->cargosExtra = $request->data['payment']['extraCharges']; //$detalle['cargosExtra'];
            }

            $nDetalle->tomarFoto = true;
            $nDetalle->instrucciones = $detail['instEntrega']; //$detalle['instrucciones'];
            $nDetalle->coordsDestino = NULL; //$detalle['coordsDestino'];
            $nDetalle->save();

            if (isset($request->data['extra'])) {
                $nECOrder = new ExtraChargesOrders();
                $nECOrder->idDetalle = $nDetalle->idDetalle;
                $nECOrder->idCargoExtra = $request->data['extra']["idCargoExtra"];
                $nECOrder->idDetalleOpcion = $request->data['extra']["idDetalleOpcion"];
                $nECOrder->save();
            }

            $newTransaction = new DeliveryTransaction();
            $newTransaction->idDelivery = $lastId;
            $newTransaction->idCliente = $customer;
            $newTransaction->reasonCode = $request->data['transactionDetails']['reasonCode'];
            $newTransaction->reasonCodeDescription = $request->data['transactionDetails']['reasonCodeDescription'];
            $newTransaction->authCode = $request->data['transactionDetails']['authCode'];
            $newTransaction->orderNumber = $request->data['transactionDetails']['orderNumber'];
            $newTransaction->fechaRegistro = Carbon::now();
            $newTransaction->save();

            $receivers = $customerDetails->email;
            $this->sendmail($receivers, $lastId);

            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Solicitud de Delivery enviada correctamente.
                        Recibirás un email con los detalles de tu reserva.',
                    'nDelivery' => $lastId
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array(
                'User' => Auth::user()->nomUsuario,
                'context' => $ex->getTrace()
            ));
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Lo sentimos, ha ocurrido un error al procesar tu solicitud. Por favor intenta de nuevo.'
                ],
                500
            );
        }
    }

    //CHANGE DELIVERY HOUR

    public
    function changeDeliveryHour(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.fecha' => 'required',
            'form.hora' => 'required',
            'form.idDelivery' => 'required'
        ]);

        $rDelivery = $request->form;
        $currDelivery = Delivery::find($rDelivery['idDelivery']);

        try {
            $date = date('Y-m-d', strtotime($rDelivery['fecha']));
            $time = date('H:i', strtotime($rDelivery['hora']));
            $deliveryDayCode = Carbon::create($date)->dayOfWeek;

            $todaySchedule = Schedule::where('cod', $deliveryDayCode)->get()->first();


            if (
                Auth::user()->idPerfil == 8 && $time < $todaySchedule->inicio || Auth::user()->idPerfil == 8 &&
                $time > $todaySchedule->final
            ) {
                return response()->json(
                    [
                        'error' => 1,
                        'message' => 'Lo sentimos, la hora de reservación está fuera del horario.
                        Puede que el horario haya sido cambiado recientemente.
                        Por favor recargue la página por lo menos 2 veces para verificar el cambio.'
                    ],
                    500
                );
            }

            $datetime = $date . ' ' . $time;
            $currDelivery->update([
                'fechaReserva' => new Carbon($datetime)
            ]);

            $this->sendChangeNotification($currDelivery->email, $currDelivery->idDelivery);

            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Hora de recogida actualizada correctamente'
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array('User' => Auth::user()->nomUsuario, 'context' => $ex->getTrace()));
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al cambiar la hora de recogida. Intenta de nuevo'
                ],
                500
            );
        }
    }

    public function getTodayCustomerOrders()
    {
        try {
            $user = Auth::user();
            $deliveriesDia = DetalleDelivery::with([
                'delivery.category',
                'conductor',
                'estado',
                'photography',
                'extraCharges.extracharge',
                'extraCharges.option'])
                ->whereHas('delivery', function ($q) use ($user) {
                    $q->whereDate('fechaReserva', Carbon::today())
                        ->where('idCliente', $user->idCliente);
                })->get();
            $pedidosDia = [];

            foreach ($deliveriesDia as $dtl) {
                $dtl->fechaEntrega = \Carbon\Carbon::parse($dtl->fechaEntrega)->format('Y-m-d H:i');
                $dtl->tarifaBase = number_format($dtl->tarifaBase, 2);
                $dtl->recargo = number_format($dtl->recargo, 2);
                $dtl->cargosExtra = number_format($dtl->cargosExtra, 2);
                $dtl->cTotal = number_format($dtl->cTotal, 2);
                array_push($pedidosDia, $dtl);
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => $pedidosDia
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al cargar los datos'
                ],
                500
            );
        }
    }

    public function getAllCustomerOrders()
    {
        try {
            $user = Auth::user();
            $allDeliveries = DetalleDelivery::with(['delivery.category', 'conductor', 'estado', 'photography'])
                ->whereHas('delivery', function ($q) use ($user) {
                    $q->where('idCliente', $user->idCliente);
                })->get();

            $todosPedidos = [];

            foreach ($allDeliveries as $dtl) {
                $dtl->fechaEntrega = \Carbon\Carbon::parse($dtl->fechaEntrega)->format('Y-m-d H:i');
                $dtl->tarifaBase = number_format($dtl->tarifaBase, 2);
                $dtl->recargo = number_format($dtl->recargo, 2);
                $dtl->cargosExtra = number_format($dtl->cargosExtra, 2);
                $dtl->cTotal = number_format($dtl->cTotal, 2);
                array_push($todosPedidos, $dtl);
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => $todosPedidos
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al cargar los datos'
                ],
                500
            );
        }
    }

    public
    function getTodayCustomerDeliveries()
    {
        try {
            $user = Auth::user();
            $deliveriesDia = Delivery::where('idCliente', $user->idCliente)
                ->whereDate('fechaReserva', Carbon::today())
                ->with(['category', 'detalle', 'estado'])->get();
            foreach ($deliveriesDia as $delivery) {
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d H:i');
                $delivery->fechaReserva = date('d-m-Y h:i', strtotime($delivery->fechaReserva));
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->cargosExtra = number_format($delivery->cargosExtra, 2);
                $delivery->total = number_format($delivery->total, 2);
            }


            return response()->json(
                [
                    'error' => 0,
                    'data' => $deliveriesDia
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al cargar los datos'
                ],
                500
            );
        }
    }

    public
    function getAllCustomerDeliveries()
    {
        try {
            $user = Auth::user();

            $allDeliveries = Delivery::where('idCliente', $user->idCliente)
                ->with(['category', 'detalle.conductor', 'estado'])->orderBy('fechaReserva', 'DESC')->get();

            foreach ($allDeliveries as $delivery) {
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d H:i');
                //$delivery->fechaReserva = date('d-m-Y h:i', strtotime($delivery->fechaReserva));
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->cargosExtra = number_format($delivery->cargosExtra, 2);
                $delivery->total = number_format($delivery->total, 2);
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => $allDeliveries
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al cargar los datos'
                ],
                500
            );
        }
    }


    /****
     * GET DELIVERIES FUNCTIONS
     ****/


    public
    function getOrdersByCustomer(Request $request)
    {
        $request->validate(['customerId' => 'required']);
        $custId = $request->customerId;

        try {
            $allDeliveries = DetalleDelivery::with(['delivery.category', 'estado', 'conductor'])
                ->whereIn('idEstado', [44, 46, 47])
                ->whereHas('delivery', function ($q) use ($custId) {
                    $q->where('idCliente', $custId);
                })->get();

            $todosPedidos = [];

            foreach ($allDeliveries as $dtl) {
                $dtl->fechaEntrega = \Carbon\Carbon::parse($dtl->fechaEntrega)->format('Y-m-d H:i');
                $dtl->tarifaBase = number_format($dtl->tarifaBase, 2);
                $dtl->recargo = number_format($dtl->recargo, 2);
                $dtl->cargosExtra = number_format($dtl->cargosExtra, 2);
                $dtl->cTotal = number_format($dtl->cTotal, 2);
                array_push($todosPedidos, $dtl);
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => array('todos' => $todosPedidos)
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array('User' => Auth::user()->nomUsuario, 'context' => $ex->getTrace()));
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al cargar los datos'
                ],
                500
            );
        }
    }

    /*
        public function generateContract(Request $request)
        {
            $request->validate(['idDelivery' => 'required', 'vehiculoAsignado' => 'required']);
            $idDelivery = $request->idDelivery;
            $vehiculo = $request->motivoAnul;

            try {
                $tarifa = Tarifa::all();
                $newContract = new ContratoDelivery();
                $currDelivery = Delivery::where('idDelivery', $idDelivery)->get();
                $ordersCount = $currDelivery->detalle()->count();
                $newContract->idDelivery = $idDelivery;
                $newContract->idTarifaDelivery = ;
                $newContract->idDelivery = ;
                $newContract->idDelivery = ;
                $newContract->idDelivery = ;
                $newContract->idDelivery = ;

                $currDelivery->idEstado = 48;
                $currDelivery->numContrato = $motivoAnul;
                $currDelivery->save();*/

    /*$receivers = $hDelivery['email'];
    $orderDelivery = DetalleDelivery::where('idDelivery', $nDelivery['idDelivery'])->get();
    $this->sendmail($receivers, $nDelivery, $orderDelivery);

    return response()->json(
        [
            'error' => 0,
            'message' => 'Delivery anulada correctamente.',

        ],
        200
    );

    } catch (Exception $ex) {
    Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
    return response()->json(
        [
            'error' => 1,
            'message' => $ex->getMessage()
        ],
        500
    );
    }

    }*/

    /****
     * UPDATE DELIVERIES FUNCTIONS
     ****/
    public
    function assignDelivery(Request $request)
    {
        $idConductor = $request->assignForm['idConductor'];
        //$idVehiculo = $request->asignForm['idVehiculo'];
        $idDelivery = $request->idDelivery;
        try {
            $delivery = Delivery::where('idDelivery', $idDelivery);
            $delivery->update(['idEstado' => 37]);

            DetalleDelivery::where('idDelivery', $idDelivery)
                ->update(['idEstado' => 37, 'idConductor' => $idConductor]);

            $conductor = User::where('idUsuario', $idConductor)->get()->first();

            $nCtrl = new CtrlEstadoDelivery();
            $nCtrl->idDelivery = $idDelivery;
            $nCtrl->idEstado = 37;
            $nCtrl->idUsuario = Auth::user()->idUsuario;
            $nCtrl->fechaRegistro = Carbon::now();
            $nCtrl->save();

            return response()->json(
                [
                    'error' => 0,
                    'data' => 'Reserva asignada correctamente a: ' . $conductor->nomUsuario
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al asignar la reserva'
                ],
                500
            );
        }
    }

    public
    function changeStateDelivery(Request $request)
    {
        $idEstado = $request->idEstado['idEstado'];
        $idDelivery = $request->idDelivery;
        try {
            $delivery = Delivery::where('idDelivery', $idDelivery);

            $delivery->update(['idEstado' => $idEstado]);

            $details = DetalleDelivery::where('idDelivery', $idDelivery);
            $details->update(['idEstado' => $idEstado]);
            $estado = Estado::where('idEstado', $idEstado)->get()->first();

            $nCtrl = new CtrlEstadoDelivery();
            $nCtrl->idDelivery = $idDelivery;
            $nCtrl->idEstado = $idEstado;
            $nCtrl->idUsuario = Auth::user()->idUsuario;
            $nCtrl->fechaRegistro = Carbon::now();
            $nCtrl->save();


            return response()->json(
                [
                    'error' => 0,
                    'data' => 'Se cambió el estado de reserva a: ' . $estado->descEstado
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al cambiar el estado de la reserva'
                ],
                500
            );
        }
    }

    public
    function assignOrder(Request $request)
    {
        $idConductor = $request->idConductor;
        $idDetalle = $request->idDetalle;
        try {

            DetalleDelivery::where('idDetalle', $idDetalle)
                ->update(['idEstado' => 41, 'idConductor' => $idConductor]);

            $conductor = User::where('idUsuario', $idConductor)->get()->first();

            $nCtrl = new CtrlEstadoDelivery();
            $nCtrl->idDetalle = $idDetalle;
            $nCtrl->idEstado = 41;
            $nCtrl->idUsuario = Auth::user()->idUsuario;
            $nCtrl->fechaRegistro = Carbon::now();
            $nCtrl->save();


            return response()->json(
                [
                    'error' => 0,
                    'data' => 'Envío asignado correctamente a: ' . $conductor->nomUsuario
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al asignar el envío'
                ],
                500
            );
        }
    }

    public
    function assignOrderAuxiliar(Request $request)
    {
        $idAuxiliar = $request->idAuxiliar;
        $idDetalle = $request->idDetalle;
        try {
            $detail = DetalleDelivery::where('idDetalle', $idDetalle);
            if ($detail->get()->first()->idConductor != $request->idAuxiliar) {
                $detail->update(['idAuxiliar' => $request->idAuxiliar]);
                $conductor = User::where('idUsuario', $idAuxiliar)->get()->first();

                return response()->json(
                    [
                        'error' => 0,
                        'data' => $conductor->nomUsuario . ' Ha sido asignado correctamente como auxiliar de envío'
                    ],
                    200
                );
            } else {
                return response()->json(
                    [
                        'error' => 1,
                        'message' => 'El conductor no puede ser asignado también como auxiliar'
                    ],
                    500
                );
            }
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al asignar el envío'
                ],
                500
            );
        }
    }

    public
    function addOrderExtracharge(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.idDetalle' => 'required',
            'form.idCargoExtra' => 'required'
        ]);

        $ecId = $request->form['idCargoExtra'];
        $orderId = $request->form['idDetalle'];

        try {
            $currOrder = DetalleDelivery::where('idDetalle', $orderId);
            if ($request->form['observaciones'] != null) {
                $currOrder->update([
                    'observaciones' => $request->form['observaciones']
                ]);
            }

            if ($currOrder->get()->first()->refNumber != null) {
                return response()->json(
                    [
                        'error' => 1,
                        'message' => 'No se puede agregar el cargo extra porque el envío seleccionado ya se encuentra facturado.'
                    ],
                    500
                );
            }

            if (isset($request->form['idOpcionExtra'])) {
                $exist = ExtraChargesOrders::where('idDetalle', $orderId)
                    ->where('idCargoExtra', $ecId)
                    ->where('idDetalleOpcion', $request->form['idOpcionExtra'])
                    ->count();

                if ($exist == 0) {
                    $ecOption = DetalleOpcionesCargosExtras::where('idDetalleOpcion', $request->form['idOpcionExtra'])
                        ->get()
                        ->first();

                    $currOrder->update([
                        'cargosExtra' => $currOrder->get()->first()->cargosExtra + $ecOption->costo,
                        'cTotal' => $currOrder->get()->first()->cTotal + $ecOption->costo
                    ]);

                    $currDelivery = $currOrder->get()->first()->delivery;
                    $currDelivery->update([
                        'cargosExtra' => $currDelivery->cargosExtra + $ecOption->costo,
                        'total' => $currDelivery->total + $ecOption->costo
                    ]);
                    $nECOrder = new ExtraChargesOrders();
                    $nECOrder->idDetalle = $orderId;
                    $nECOrder->idCargoExtra = $ecId;
                    $nECOrder->idDetalleOpcion = $request->form['idOpcionExtra'];
                    $nECOrder->save();

                    $extrachargeItemDet = ItemDetail::where('idDetalleOpcion', $request->form['idOpcionExtra'])
                        ->get()->first();
                    if ($extrachargeItemDet != null) {
                        $nSurValFact = new OrderExtrachargeFactValues();
                        $nSurValFact->idDetalle = $orderId;
                        $nSurValFact->tYK = $extrachargeItemDet->tYK;
                        $nSurValFact->cobVehiculo = $extrachargeItemDet->cobVehiculo;
                        $nSurValFact->servChofer = $extrachargeItemDet->servChofer;
                        $nSurValFact->recCombustible = $extrachargeItemDet->recCombustible;
                        $nSurValFact->cobTransporte = $extrachargeItemDet->cobTransporte;
                        $nSurValFact->isv = $extrachargeItemDet->isv;
                        $nSurValFact->tasaTuris = $extrachargeItemDet->tasaTuris;
                        $nSurValFact->gastosReembolsables = $extrachargeItemDet->gastosReembolsables;
                        $nSurValFact->save();
                    }
                }
            } else {
                $ec = ExtraCharge::where('idCargoExtra', $ecId)
                    ->get()
                    ->first();

                $exist = ExtraChargesOrders::where('idDetalle', $orderId)
                    ->where('idCargoExtra', $ecId)
                    ->count();

                if ($exist == 0) {
                    if (isset($request->form['montoCargoVariable'])) {
                        $currOrder->update([
                            'cargosExtra' => $currOrder->get()->first()->cargosExtra + $request->form['montoCargoVariable'],
                            'cTotal' => $currOrder->get()->first()->cTotal + $request->form['montoCargoVariable']
                        ]);

                        $currDelivery = $currOrder->get()->first()->delivery;
                        $currDelivery->update([
                            'cargosExtra' => $currDelivery->cargosExtra + $request->form['montoCargoVariable'],
                            'total' => $currDelivery->total + $request->form['montoCargoVariable']
                        ]);
                    } else {
                        $currOrder->update([
                            'cargosExtra' => $currOrder->get()->first()->cargosExtra + $ec->costo,
                            'cTotal' => $currOrder->get()->first()->cTotal + $ec->costo
                        ]);

                        $currDelivery = $currOrder->get()->first()->delivery;
                        $currDelivery->update([
                            'cargosExtra' => $currDelivery->cargosExtra + $ec->costo,
                            'total' => $currDelivery->total + $ec->costo
                        ]);
                    }

                    $nECOrder = new ExtraChargesOrders();
                    $nECOrder->idDetalle = $orderId;
                    $nECOrder->idCargoExtra = $ecId;
                    $nECOrder->save();


                    $extrachargeItemDet = ItemDetail::where('idCargoExtra', $ecId)
                        ->get()->first();
                    if ($extrachargeItemDet != null) {
                        $nSurValFact = new OrderExtrachargeFactValues();
                        $nSurValFact->idDetalle = $orderId;
                        $nSurValFact->tYK = $extrachargeItemDet->tYK;
                        $nSurValFact->cobVehiculo = $extrachargeItemDet->cobVehiculo;
                        $nSurValFact->servChofer = $extrachargeItemDet->servChofer;
                        $nSurValFact->recCombustible = $extrachargeItemDet->recCombustible;
                        $nSurValFact->cobTransporte = $extrachargeItemDet->cobTransporte;
                        $nSurValFact->isv = $extrachargeItemDet->isv;
                        $nSurValFact->tasaTuris = $extrachargeItemDet->tasaTuris;
                        $nSurValFact->gastosReembolsables = $extrachargeItemDet->gastosReembolsables;
                        $nSurValFact->save();
                    }

                }
            }

            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Se agregó correctamente el cargo extra al envío'
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al agregar el cargo extra'
                ],
                500
            );
        }
    }

    public
    function removeOrderExtracharge(Request $request)
    {
        $request->validate([
            'idDetalle' => 'required',
            'id' => 'required'
        ]);

        $ecOrderId = $request->id;
        $orderId = $request->idDetalle;

        try {
            $currOrder = DetalleDelivery::where('idDetalle', $orderId);
            $currEcOrder = ExtraChargesOrders::where('id', $ecOrderId);

            if ($currEcOrder->get()->first()->idDetalleOpcion != null) {
                $ecOption = $currEcOrder->get()->first()->option;

                $currOrder->update([
                    'cargosExtra' => $currOrder->get()->first()->cargosExtra - $ecOption->costo,
                    'cTotal' => $currOrder->get()->first()->cTotal - $ecOption->costo
                ]);

                $currDelivery = $currOrder->get()->first()->delivery;
                $currDelivery->update([
                    'cargosExtra' => $currDelivery->cargosExtra - $ecOption->costo,
                    'total' => $currDelivery->total - $ecOption->costo
                ]);
                $currEcOrder->delete();
            } else {
                $ec = $currEcOrder->get()->first()->extracharge;
                if (isset($request->form['montoCargoVariable'])) {
                    $currOrder->update([
                        'cargosExtra' => $currOrder->get()->first()->cargosExtra - $request->form['montoCargoVariable'],
                        'cTotal' => $currOrder->get()->first()->cTotal - $request->form['montoCargoVariable']
                    ]);

                    $currDelivery = $currOrder->get()->first()->delivery;
                    $currDelivery->update([
                        'cargosExtra' => $currDelivery->cargosExtra - $request->form['montoCargoVariable'],
                        'total' => $currDelivery->total - $request->form['montoCargoVariable']
                    ]);
                } else {
                    $currOrder->update([
                        'cargosExtra' => $currOrder->get()->first()->cargosExtra - $ec->costo,
                        'cTotal' => $currOrder->get()->first()->cTotal - $ec->costo
                    ]);

                    $currDelivery = $currOrder->get()->first()->delivery;
                    $currDelivery->update([
                        'cargosExtra' => $currDelivery->cargosExtra - $ec->costo,
                        'total' => $currDelivery->total - $ec->costo
                    ]);
                }

                $currEcOrder->delete();
            }

            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Se eliminó correctamente el cargo extra del envío'
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al agregar el cargo extra'
                ],
                500
            );
        }
    }

    public
    function changeOrderState(Request $request)
    {
        $request->validate([
            'idEstado' => 'required',
            'idDetalle' => 'required'
        ]);

        $stateId = $request->idEstado;
        $orderId = $request->idDetalle;
        $observ = $request->observaciones;
        try {

            $details = DetalleDelivery::where('idDetalle', $orderId);
            if ($stateId == 44 || $stateId == 46 || $stateId == 47) {
                $details->update([
                    'idEstado' => $stateId,
                    'observaciones' => $observ,
                    'fechaEntrega' => Carbon::now()
                ]);
            } else {
                $details->update([
                    'idEstado' => $stateId,
                    'observaciones' => $observ
                ]);
            }

            $estado = Estado::where('idEstado', $stateId)->get()->first();

            $nCtrl = new CtrlEstadoDelivery();
            $nCtrl->idDetalle = $orderId;
            $nCtrl->idEstado = $stateId;
            $nCtrl->idUsuario = Auth::user()->idUsuario;
            $nCtrl->fechaRegistro = Carbon::now();
            $nCtrl->save();

            $currDel = $details->get('idDelivery')->first()->idDelivery;
            $currDelDetails = DetalleDelivery::where('idDelivery', $currDel)->get();
            $counter = 0;

            foreach ($currDelDetails as $order) {
                if ($order->idEstado == 44 || $order->idEstado == 46 || $order->idEstado == 47) {
                    $counter++;
                }
            }

            if ($counter == sizeof($currDelDetails)) {
                Delivery::where('idDelivery', $currDel)
                    ->update(['idEstado' => 39]);
                $nCtrl = new CtrlEstadoDelivery();
                $nCtrl->idDelivery = $currDel;
                $nCtrl->idEstado = 39;
                $nCtrl->idUsuario = Auth::user()->idUsuario;
                $nCtrl->fechaRegistro = Carbon::now();
                $nCtrl->save();
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => 'Se cambió el estado del envío a: ' . $estado->descEstado
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al cambiar el estado del envío'
                ],
                500
            );
        }
    }

    public
    function finishDelivery(Request $request)
    {

        $idDelivery = $request->idDelivery;
        try {
            $delivery = Delivery::where('idDelivery', $idDelivery);
            $delivery->update(['idEstado' => 39]);

            $details = DetalleDelivery::where('idDelivery', $idDelivery);
            $details->update(['idEstado' => 39]);

            $nCtrl = new CtrlEstadoDelivery();
            $nCtrl->idDelivery = $idDelivery;
            $nCtrl->idEstado = 39;
            $nCtrl->idUsuario = Auth::user()->idUsuario;
            $nCtrl->fechaRegistro = Carbon::now();
            $nCtrl->save();

            return response()->json(
                [
                    'error' => 0,
                    'data' => 'Reserva finalizada correctamente.'
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al finalizar la reserva'
                ],
                500
            );
        }
    }

    public
    function cancelDelivery(Request $request)
    {
        $request->validate(['id' => 'required']);
        $idDelivery = $request->id;

        try {
            $currDelivery = Delivery::where('idDelivery', $idDelivery)->get()->first();
            $currDelivery->idEstado = 36;
            $currDelivery->usrAnuloReserva = Auth::user()->idUsuario;
            $currDelivery->fechaAnulado = Carbon::now();
            $currDelivery->motivoAnul = 'Cancelada por el cliente';
            $currDelivery->save();

            DetalleDelivery::where('idDelivery', $idDelivery)
                ->update([
                    'idEstado' => 42,
                    'observaciones' => 'Cliente esperó ventana de 30 minutos y no fue conectado con un conductor.'
                ]);

            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Delivery cancelada correctamente.',
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al cancelar la reserva'
                ],
                500
            );
        }
    }

    public
    function updateDeliveried(Request $request)
    {
        $request->validate([
            'idDetalle' => 'required',
            'idConductor' => ' required',
            'nomRecibio' => 'required',
            'fechaEntrega' => 'required'
        ]);
        $idDetalle = $request->idDetalle;
        $idConductor = $request->idConductor;
        $nomRecibio = $request->nomRecibio;
        $fechaEntrega = new Carbon($request->fechaEntrega);
        try {
            $detail = DetalleDelivery::where('idDetalle', $idDetalle);
            $detail->update([
                'idConductor' => $idConductor,
                'nomRecibio' => $nomRecibio,
                'fechaEntrega' => $fechaEntrega,
                'entregado' => true
            ]);

            return response()->json(
                [
                    'codError' => 0,
                    'messageError' => null,
                    'message' => 'la entrega se registró correctamente'
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(['codError' => 1, 'messageError' => $ex->getMessage()], 500);
        }
    }

    /*****
     * FOR TEST FUNCTIONS
     *****/

    public
    function testContractFormat(Request $request)
    {
        $delivery = Delivery::where('idDelivery', $request->idDelivery)->get()->first();
        $orderDelivery = DetalleDelivery::where('idDelivery', $delivery->idDelivery)->get();

        return view('deliveryContract', compact('delivery', 'orderDelivery'));
    }

    /*public function testReserveFormat(Request $request)
    {
        $delivery = Delivery::where('idDelivery', $request->idDelivery)->get()->first();
        $orderDelivery = DetalleDelivery::where('idDelivery', $delivery->idDelivery)->get();

        return view('applicationSheet', compact('delivery', 'orderDelivery'));

    }*/

    public
    function testSendMail(Request $request)
    {
        $idDelivery = $request->idDelivery;
        $this->sendmail('jylrivera96@gmail.com', $idDelivery);
    }

    /****
     * FUNCTIONS FOR MAIL SENDING
     ****/

    public
    function sendChangeNotification($mail, $idDelivery)
    {
        $delivery = Delivery::where('idDelivery', $idDelivery)->get()->first();
        $data["email"] = $mail;
        $data["client_name"] = $delivery->nomCliente;
        $data["subject"] = 'Xplore Delivery - Cambio de Hora';
        $data["delivery"] = $delivery;
        $data["from"] = 'Xplore Delivery';

        try {
            Mail::send('changeNotification', $data, function ($message) use ($data) {
                $message
                    ->from('noreply@xplorerentacar.com', $data["from"])
                    ->to($data["email"], $data["client_name"])
                    ->subject($data["subject"]);
            });
        } catch (Exception $exception) {
            $this->serverstatuscode = "0";
            $this->serverstatusdes = $exception->getMessage();
        }
        if (Mail::failures()) {
            return false;
        } else {
            return true;
        }
    }

    public
    function sendmail($mail, $idDelivery)
    {
        $delivery = Delivery::where('idDelivery', $idDelivery)->get()->first();
        $data["email"] = $mail;
        $data["client_name"] = $delivery->nomCliente;
        $data["subject"] = 'Xplore Delivery No. ' . $delivery->idDelivery;
        $data["delivery"] = $delivery;
        $orders = DetalleDelivery::where('idDelivery', $idDelivery)->get();
        $data["orderDelivery"] = $orders;
        $data["from"] = 'Xplore Delivery';


        try {
            Mail::send('applicationSheet', $data, function ($message) use ($data) {
                $message
                    ->from('noreply@xplorerentacar.com', $data["from"])
                    ->to($data["email"], $data["client_name"])
                    ->subject($data["subject"]);
            });
        } catch (Exception $exception) {
            $this->serverstatuscode = "0";
            $this->serverstatusdes = $exception->getMessage();
        }
        if (Mail::failures()) {
            return false;
        } else {
            return true;
        }
    }

    public function changeDestinationAddress(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.idDetalle' => 'required',
            'form.idDelivery' => 'required',
            'form.direccion' => 'required',
            'form.distancia' => 'required',
            'form.tiempo' => 'required',
            'form.coordsDestino' => 'required',
            'form.recargo' => 'required',
            'form.cTotal' => 'required'
        ]);

        $order = $request->form['idDetalle'];
        $newAddress = $request->form['direccion'];
        try {
            $ordToUp = DetalleDelivery::where('idDetalle', $order);
            $delivery = Delivery::where('idDelivery', $ordToUp->get()->first()->idDelivery);

            if ($ordToUp->get()->first()->refNumber != null) {
                return response()->json(
                    [
                        'error' => 1,
                        'message' => 'No se puede cambiar la dirección del envío porque ya se encuentra facturado.'
                    ],
                    500
                );
            }

            //restar valores antiguos
            $nSurch = $delivery->get()->first()->recargos - $ordToUp->get()->first()->recargo;
            $nTotal = $delivery->get()->first()->total - $ordToUp->get()->first()->cTotal;

            $delivery->update([
                'recargos' => $nSurch,
                'total' => $nTotal
            ]);

            $ordToUp->update([
                'direccion' => $newAddress,
                'distancia' => $request->form['distancia'],
                'tiempo' => $request->form['tiempo'],
                'coordsDestino' => $request->form['coordsDestino'],
                'recargo' => $request->form['recargo'],
                'cTotal' => $request->form['cTotal'] + $ordToUp->get()->first()->cargosExtra
            ]);


            if ($request->form['idRecargo'] != null) {
                $surchargeItemDet = ItemDetail::where('idRecargo', $request->form['idRecargo'])
                    ->get()->first();
                if ($surchargeItemDet != null) {
                    $nSurValFact = OrderSurchargeFactValues::where('idDetalle', $order);
                    $nSurValFact->update([
                        'tYK' => $surchargeItemDet->tYK,
                        'cobVehiculo' => $surchargeItemDet->cobVehiculo,
                        'servChofer' => $surchargeItemDet->servChofer,
                        'recCombustible' => $surchargeItemDet->recCombustible,
                        'cobTransporte' => $surchargeItemDet->cobTransporte,
                        'isv' => $surchargeItemDet->isv,
                        'tasaTuris' => $surchargeItemDet->tasaTuris,
                        'gastosReembolsables' => $surchargeItemDet->gastosReembolsables,
                    ]);
                }

            } else {
                $nSurValFact = OrderSurchargeFactValues::where('idDetalle', $order);
                $nSurValFact->update([
                    'tYK' => 0.00,
                    'cobVehiculo' => 0.00,
                    'servChofer' => 0.00,
                    'recCombustible' => 0.00,
                    'cobTransporte' => 0.00,
                    'isv' => 0.00,
                    'tasaTuris' => 0.00,
                    'gastosReembolsables' => 0.00,
                ]);
            }

            $delivery->update([
                'recargos' => $delivery->get()->first()->recargos + $request->form['recargo'],
                'total' => $delivery->get()->first()->total + $request->form['cTotal'] + $ordToUp->get()->first()->cargosExtra
            ]);

            Log::info('Direccion Modificada', ['envio' => $order, 'usuario' => Auth::user()->idUsuario]);

            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Dirección de entrega actualizada correctamente'
                ],
                200
            );

        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Ocurrió un error al actualizar la dirección'
                ],
                500
            );
        }
    }
}
