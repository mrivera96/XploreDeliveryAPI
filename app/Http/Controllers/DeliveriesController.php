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
use App\Schedule;
use App\Tarifa;
use App\User;
use Exception;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
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
                $delivery = Delivery::with(['estado', 'detalle.conductor', 'detalle.estado', 'detalle.photography'])
                    ->where('idDelivery', $request->id)->with(['category', 'detalle'])
                    ->get()->first();
            } else {
                $delivery = Delivery::with(['estado', 'detalle.conductor', 'detalle.estado', 'detalle.photography', 'category'])
                    ->where('idCliente', Auth::user()->idCliente)->where('idDelivery', $request->id)
                    ->get()->first();
            }

            $delivery->fechaNoFormatted = $delivery->fechaReserva;
            $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('d/m/Y, h:i a');
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
                    'data' => $delivery
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

    public function getTodayOrders()
    {
        try {
            $deliveriesDia = DetalleDelivery::with([
                'delivery', 'estado', 'conductor', 'photography',
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
                'delivery', 'estado', 'conductor', 'auxiliar',
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
                    'message' => $ex->getTrace() //'Ocurrió un error al cargar los datos'
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
            $orders = DetalleDelivery::with([
                'delivery', 'estado', 'conductor', 'photography', 'auxiliar',
                'extraCharges.extracharge', 'extraCharges.option'
            ])
                ->whereHas('delivery', function ($q) use ($request) {
                    $q->whereBetween('fechaReserva', [
                        $request->form['initDate'] . ' 00:00:00',
                        $request->form['finDate'] . ' 23:59:59'
                    ]);
                })
                ->get();
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
        if ($driver != -1) {
            $driverDetails = User::where('idUsuario', $driver)->get()->first();
        }

        $initDateTime = new Carbon(date('Y-m-d', strtotime($form['initDate'])) . ' 00:00:00');
        $finDateTime = new Carbon(date('Y-m-d', strtotime($form['finDate'])) . ' 23:59:59');

        try {
            $outputData = [];

            if ($driver == -1) {
                $drivers = User::where('idPerfil', 7)->get();
                $orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->orderBy('fechaEntrega', 'desc')->get()
                    ->groupBy(function ($val) {
                        return Carbon::parse($val->fechaEntrega)->format('Y-m-d');
                    });

                foreach ($drivers as $driver) {
                    foreach ($orders as $order) {
                        for ($i = 0; $i < sizeof($order); $i++) {
                            if ($driver->idUsuario == $order[$i]->idConductor) {
                                $dataObj = (object)array();
                                $dataObj->driver = $driver->nomUsuario;
                                $dataObj->fecha = Carbon::parse($order[$i]->fechaEntrega)->format('Y-m-d');

                                $moto = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                                    ->where([
                                        'idConductor' => $order[$i]->idConductor,
                                    ])
                                    ->whereDate('fechaEntrega', $dataObj->fecha)
                                    ->whereHas('delivery', function ($q) {
                                        $q->where('idCategoria', 6);
                                    });

                                $dataObj->moto = $moto->count();

                                $tCounterMoto = 0;
                                $mCounterMoto = 0;
                                $o20CounterMoto = 0;

                                foreach ($moto->get() as $mto) {
                                    if ($mto->tiempo != null) {
                                        if (strpos($mto->tiempo, 'hour')) {
                                            $stime = explode(' ', $mto->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($mto->distancia) > 20) {
                                                $o20CounterMoto = $o20CounterMoto + intval($time);
                                            }
                                            $mto->tiempo = 30 + intval($time);
                                            $tCounterMoto = $tCounterMoto + intval($mto->tiempo);
                                        } else {
                                            if (floatval($mto->distancia) > 20) {
                                                $o20CounterMoto = $o20CounterMoto + intval($mto->tiempo);
                                            }
                                            $mto->tiempo = 30 + intval($mto->tiempo);
                                            $tCounterMoto = $tCounterMoto + intval($mto->tiempo);
                                        }
                                    }

                                    $mCounterMoto = $mCounterMoto + $mto->efectivoRecibido;
                                }

                                $dataObj->motoTime = $tCounterMoto;
                                $dataObj->motoMoney = $mCounterMoto;
                                $dataObj->motoOver20kms = $o20CounterMoto;

                                $turismo = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                                    ->where([
                                        'idConductor' => $order[$i]->idConductor,
                                    ])
                                    ->whereDate('fechaEntrega', $dataObj->fecha)
                                    ->whereHas('delivery', function ($q) {
                                        $q->where('idCategoria', 1);
                                    });

                                $dataObj->turismo = $turismo->count();

                                $tCounterTurismo = 0;
                                $mCounterTurismo = 0;
                                $o20CounterTurismo = 0;
                                foreach ($turismo->get() as $trsmo) {
                                    if ($trsmo->tiempo != null) {
                                        if (strpos($trsmo->tiempo, 'hour')) {
                                            $stime = explode(' ', $trsmo->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($trsmo->distancia) > 20) {
                                                $o20CounterTurismo = $o20CounterTurismo + intval($time);
                                            }
                                            $trsmo->tiempo = 30 + intval($time);
                                            $tCounterTurismo = $tCounterTurismo + intval($trsmo->tiempo);
                                        } else {
                                            if (floatval($trsmo->distancia) > 20) {
                                                $o20CounterTurismo = $o20CounterTurismo + intval($trsmo->tiempo);
                                            }
                                            $trsmo->tiempo = 30 + intval($trsmo->tiempo);
                                            $tCounterTurismo = $tCounterTurismo + intval($trsmo->tiempo);
                                        }
                                    }
                                    $mCounterTurismo = $mCounterTurismo + $trsmo->efectivoRecibido;
                                }
                                $dataObj->turismoTime = $tCounterTurismo;
                                $dataObj->turismoMoney = $mCounterTurismo;
                                $dataObj->turismoOver20kms = $o20CounterTurismo;

                                $pickUp = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                                    ->where([
                                        'idConductor' => $order[$i]->idConductor,
                                    ])
                                    ->whereDate('fechaEntrega', $dataObj->fecha)
                                    ->whereHas('delivery', function ($q) {
                                        $q->where('idCategoria', 2);
                                    });

                                $dataObj->pickup = $pickUp->count();

                                $tCounterPickup = 0;
                                $mCounterPickup = 0;
                                $o20CounterPickup = 0;
                                foreach ($pickUp->get() as $pckup) {
                                    if ($pckup->tiempo != null) {
                                        if (strpos($pckup->tiempo, 'hour')) {
                                            $stime = explode(' ', $pckup->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($pckup->distancia) > 20) {
                                                $o20CounterPickup = $o20CounterPickup + intval($time);
                                            }
                                            $pckup->tiempo = 40 + intval($time);
                                            $tCounterPickup = $tCounterPickup + intval($pckup->tiempo);
                                        } else {
                                            if (floatval($pckup->distancia) > 20) {
                                                $o20CounterPickup = $o20CounterPickup + intval($pckup->tiempo);
                                            }
                                            $pckup->tiempo = 40 + intval($pckup->tiempo);
                                            $tCounterPickup = $tCounterPickup + intval($pckup->tiempo);
                                        }
                                    }
                                    $mCounterPickup = $mCounterPickup + $pckup->efectivoRecibido;
                                }
                                $dataObj->pickupTime = $tCounterPickup;
                                $dataObj->pickupMoney = $mCounterPickup;
                                $dataObj->pickupOver20kms = $o20CounterPickup;

                                $panel = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                                    ->where([
                                        'idConductor' => $order[$i]->idConductor,
                                    ])
                                    ->whereDate('fechaEntrega', $dataObj->fecha)
                                    ->whereHas('delivery', function ($q) {
                                        $q->where('idCategoria', 3);
                                    });

                                $dataObj->panel = $panel->count();

                                $tCounterPanel = 0;
                                $mCounterPanel = 0;
                                $o20CounterPanel = 0;
                                foreach ($panel->get() as $pnl) {
                                    if ($pnl->tiempo != null) {
                                        if (strpos($pnl->tiempo, 'hour')) {
                                            $stime = explode(' ', $pnl->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($pnl->distancia) > 20) {
                                                $o20CounterPanel = $o20CounterPanel + intval($time);
                                            }
                                            $pnl->tiempo = 40 + intval($time);
                                            $tCounterPanel = $tCounterPanel + intval($pnl->tiempo);
                                        } else {
                                            if (floatval($pnl->distancia) > 20) {
                                                $o20CounterPanel = $o20CounterPanel + intval($pnl->tiempo);
                                            }
                                            $pnl->tiempo = 40 + intval($pnl->tiempo);
                                            $tCounterPanel = $tCounterPanel + intval($pnl->tiempo);
                                        }
                                    }
                                    $mCounterPanel = $mCounterPanel + $pnl->efectivoRecibido;
                                }
                                $dataObj->panelTime = $tCounterPanel;
                                $dataObj->panelMoney = $mCounterPanel;
                                $dataObj->panelOver20kms = $o20CounterPanel;

                                $pickUpAuxiliar = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                                    ->where([
                                        'idConductor' => $order[$i]->idConductor,
                                    ])
                                    ->whereDate('fechaEntrega', $dataObj->fecha)
                                    ->whereHas('delivery', function ($q) {
                                        $q->where('idCategoria', 4);
                                    });

                                $dataObj->pickupAuxiliar = $pickUpAuxiliar->count();

                                $tCounterPickupAuxiliar = 0;
                                $mCounterPickupAuxiliar = 0;
                                $o20CounterPickupAuxiliar = 0;
                                foreach ($pickUpAuxiliar->get() as $pckAux) {
                                    if ($pckAux->tiempo != null) {
                                        if (strpos($pckAux->tiempo, 'hour')) {
                                            $stime = explode(' ', $pckAux->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($pckAux->distancia) > 20) {
                                                $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                            }
                                            $pckAux->tiempo = 40 + intval($time);
                                            $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($pckAux->tiempo);
                                        } else {
                                            if (floatval($pckAux->distancia) > 20) {
                                                $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($pckAux->tiempo);
                                            }
                                            $pckAux->tiempo = 40 + intval($pckAux->tiempo);
                                            $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($pckAux->tiempo);
                                        }
                                    }

                                    $mCounterPickupAuxiliar = $mCounterPickupAuxiliar + $pckAux->efectivoRecibido;
                                }

                                $dataObj->pickupAuxiliarTime = $tCounterPickupAuxiliar;
                                $dataObj->pickupAuxiliarMoney = $mCounterPickupAuxiliar;
                                $dataObj->pickupAuxiliarOver20kms = $o20CounterPickupAuxiliar;

                                $panelAuxiliar = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                                    ->where([
                                        'idConductor' => $order[$i]->idConductor,
                                    ])
                                    ->whereDate('fechaEntrega', $dataObj->fecha)
                                    ->whereHas('delivery', function ($q) {
                                        $q->where('idCategoria', 5);
                                    });

                                $dataObj->panelAuxiliar = $panelAuxiliar->count();

                                $tCounterPanelAuxiliar = 0;
                                $mCounterPanelAuxiliar = 0;
                                $o20CounterPanelAuxiliar = 0;
                                foreach ($panelAuxiliar->get() as $pnlAux) {
                                    if ($pnlAux->tiempo != null) {
                                        if (strpos($pnlAux->tiempo, 'hour')) {
                                            $stime = explode(' ', $pnlAux->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($pnlAux->distancia) > 20) {
                                                $o20CounterPanelAuxiliar = $o20CounterPanelAuxiliar + intval($time);
                                            }
                                            $pnlAux->tiempo = 40 + intval($time);
                                            $tCounterPanelAuxiliar = $tCounterPanelAuxiliar + intval($pnlAux->tiempo);
                                        } else {
                                            if (floatval($pnlAux->distancia) > 20) {
                                                $o20CounterPanelAuxiliar = $o20CounterPanelAuxiliar + intval($pnlAux->tiempo);
                                            }
                                            $pnlAux->tiempo = 40 + intval($pnlAux->tiempo);
                                            $tCounterPanelAuxiliar = $tCounterPanelAuxiliar + intval($pnlAux->tiempo);
                                        }
                                    }
                                    $mCounterPanelAuxiliar = $mCounterPanelAuxiliar + $pnlAux->efectivoRecibido;
                                }

                                $dataObj->panelAuxiliarTime = $tCounterPanelAuxiliar;
                                $dataObj->panelAuxiliarMoney = $mCounterPickupAuxiliar;
                                $dataObj->panelAuxiliarOver20kms = $o20CounterPanelAuxiliar;

                                $transTurism = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                                    ->where([
                                        'idConductor' => $order[$i]->idConductor,
                                    ])
                                    ->whereDate('fechaEntrega', $dataObj->fecha)
                                    ->whereHas('delivery', function ($q) {
                                        $q->where('idCategoria', 7);
                                    });

                                $dataObj->transTurism = $transTurism->count();

                                $tCounterTransTurism = 0;
                                $mCounterTransTurism = 0;
                                $o20CounterTransTurism = 0;
                                foreach ($transTurism->get() as $tturism) {
                                    if ($tturism->tiempo != null) {
                                        if (strpos($tturism->tiempo, 'hour')) {
                                            $stime = explode(' ', $tturism->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($tturism->distancia) > 20) {
                                                $o20CounterTransTurism = $o20CounterTransTurism + intval($time);
                                            }
                                            $tturism->tiempo = 20 + intval($time);
                                            $tCounterTransTurism = $tCounterTransTurism + intval($tturism->tiempo);
                                        } else {
                                            if (floatval($tturism->distancia) > 20) {
                                                $o20CounterTransTurism = $o20CounterTransTurism + intval($tturism->tiempo);
                                            }
                                            $tturism->tiempo = 20 + intval($tturism->tiempo);
                                            $tCounterTransTurism = $tCounterTransTurism + intval($tturism->tiempo);
                                        }
                                    }
                                    $mCounterTransTurism = $mCounterTransTurism + $tturism->efectivoRecibido;
                                }

                                $dataObj->transTurismTime = $tCounterTransTurism;
                                $dataObj->transTurismMoney = $mCounterTransTurism;
                                $dataObj->transTurismOver20kms = $o20CounterTransTurism;

                                $camion11 = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                                    ->where([
                                        'idConductor' => $order[$i]->idConductor,
                                    ])
                                    ->whereDate('fechaEntrega', $dataObj->fecha)
                                    ->whereHas('delivery', function ($q) {
                                        $q->where('idCategoria', 8);
                                    });

                                $dataObj->camion11 = $camion11->count();

                                $tCounterCamion11 = 0;
                                $mCounterCamion11 = 0;
                                $o20CounterCamion11 = 0;
                                foreach ($camion11->get() as $cam11) {
                                    if ($cam11->tiempo != null) {
                                        if (strpos($cam11->tiempo, 'hour')) {
                                            $stime = explode(' ', $cam11->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);
                                            if (floatval($cam11->distancia) > 20) {
                                                $o20CounterCamion11 = $o20CounterCamion11 + intval($time);
                                            }
                                            $cam11->tiempo = 40 + intval($time);
                                            $tCounterCamion11 = $tCounterCamion11 + intval($cam11->tiempo);
                                        } else {
                                            if (floatval($cam11->distancia) > 20) {
                                                $o20CounterCamion11 = $o20CounterCamion11 + intval($cam11->tiempo);
                                            }
                                            $cam11->tiempo = 40 + intval($cam11->tiempo);
                                            $tCounterCamion11 = $tCounterCamion11 + intval($cam11->tiempo);
                                        }
                                    }
                                    $mCounterCamion11 = $mCounterCamion11 + $cam11->efectivoRecibido;
                                }

                                $dataObj->camion11Time         = $tCounterCamion11;
                                $dataObj->camion11Money        = $mCounterCamion11;
                                $dataObj->camion11Over20kms    = $o20CounterCamion11;

                                $dataObj->totalOrders = $dataObj->moto + $dataObj->turismo + $dataObj->pickup + $dataObj->panel + $dataObj->pickupAuxiliar + $dataObj->panelAuxiliar + $dataObj->transTurism + $dataObj->camion11;
                                $dataObj->totalTime = $dataObj->motoTime + $dataObj->turismoTime + $dataObj->pickupTime + $dataObj->panelTime + $dataObj->pickupAuxiliarTime + $dataObj->panelAuxiliarTime + $dataObj->transTurismTime + $dataObj->camion11Time;
                                $dataObj->totalMoney = $dataObj->motoMoney + $dataObj->turismoMoney + $dataObj->pickupMoney + $dataObj->panelMoney + $dataObj->pickupAuxiliarMoney + $dataObj->panelAuxiliarMoney + $dataObj->transTurismMoney + $dataObj->camion11Money;
                                $dataObj->totalOver20kms = $dataObj->motoOver20kms + $dataObj->turismoOver20kms + $dataObj->pickupOver20kms + $dataObj->panelOver20kms + $dataObj->pickupAuxiliarOver20kms + $dataObj->panelAuxiliarOver20kms + $dataObj->transTurismOver20kms + $dataObj->camion11Over20kms;

                                $auxTime = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                                    ->where([
                                        'idAuxiliar' => $order[$i]->idConductor,
                                    ])
                                    ->whereDate('fechaEntrega', $dataObj->fecha)
                                    ->get();

                                $auxCounter = 0;

                                foreach ($auxTime as $aux) {
                                    if ($aux->tiempo != null) {
                                        if (strpos($aux->tiempo, 'hour')) {
                                            $stime = explode(' ', $aux->tiempo);
                                            $time = intval($stime[0]) * 60 + intval($stime[2]);

                                            $aux->tiempo = 30 + intval($time);
                                            $auxCounter = $auxCounter + intval($aux->tiempo);
                                        } else {
                                            $aux->tiempo = 30 + intval($aux->tiempo);
                                            $auxCounter = $auxCounter + intval($aux->tiempo);
                                        }
                                    }
                                }
                                $dataObj->totalAuxTime = $auxCounter;
                                $dataObj->tiempototal = $dataObj->totalTime + $dataObj->totalOver20kms + $dataObj->totalAuxTime;

                                $exist = 0;
                                foreach ($outputData as $output) {
                                    if ($dataObj->fecha == $output->fecha && $dataObj->driver == $output->driver) {
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
            } else {
                $orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                    ->where('idConductor', $driver)
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->orderBy('fechaEntrega', 'desc')
                    ->get()
                    ->groupBy(function ($val) {
                        return Carbon::parse($val->fechaEntrega)->format('Y-m-d');
                    });

                foreach ($orders as $order) {
                    for ($i = 0; $i < sizeof($order); $i++) {
                        $dataObj = (object)array();
                        $dataObj->driver = $driverDetails->nomUsuario;
                        $dataObj->fecha = Carbon::parse($order[$i]->fechaEntrega)->format('Y-m-d');

                        $moto = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                            ->where([
                                'idConductor' => $driver,
                            ])
                            ->whereDate('fechaEntrega', $dataObj->fecha)
                            ->whereHas('delivery', function ($q) {
                                $q->where('idCategoria', 6);
                            });

                        $dataObj->moto = $moto->count();

                        $tCounterMoto = 0;
                        $mCounterMoto = 0;
                        $o20CounterMoto = 0;
                        foreach ($moto->get() as $mto) {
                            if ($mto->tiempo != null) {
                                if (strpos($mto->tiempo, 'hour')) {
                                    $stime = explode(' ', $mto->tiempo);
                                    $time = $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($mto->distancia) > 20) {
                                        $o20CounterMoto = $o20CounterMoto + intval($time);
                                    }
                                    $mto->tiempo = 40 + intval($time);
                                    $tCounterMoto = $tCounterMoto + intval($mto->tiempo);
                                } else {
                                    if (floatval($mto->distancia) > 20) {
                                        $o20CounterMoto = $o20CounterMoto + intval($mto->tiempo);
                                    }
                                    $mto->tiempo = 40 + intval($mto->tiempo);
                                    $tCounterMoto = $tCounterMoto + intval($mto->tiempo);
                                }
                            }
                            $mCounterMoto = $mCounterMoto + $mto->efectivoRecibido;
                        }

                        $dataObj->motoTime = $tCounterMoto;
                        $dataObj->motoMoney = $mCounterMoto;
                        $dataObj->motoOver20kms = $o20CounterMoto;

                        $turismo = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                            ->where([
                                'idConductor' => $driver,
                            ])
                            ->whereDate('fechaEntrega', $dataObj->fecha)
                            ->whereHas('delivery', function ($q) {
                                $q->where('idCategoria', 1);
                            });

                        $dataObj->turismo = $turismo->count();

                        $tCounterTurismo = 0;
                        $mCounterTurismo = 0;
                        $o20CounterTurismo = 0;
                        foreach ($turismo->get() as $trsmo) {
                            if ($trsmo->tiempo != null) {
                                if (strpos($trsmo->tiempo, 'hour')) {
                                    $stime = explode(" ", $trsmo->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($trsmo->distancia) > 20) {
                                        $o20CounterTurismo = $o20CounterTurismo + intval($time);
                                    }
                                    $trsmo->tiempo = 30 + intval($time);
                                    $tCounterTurismo = $tCounterTurismo + intval($trsmo->tiempo);
                                } else {
                                    if (floatval($trsmo->distancia) > 20) {
                                        $o20CounterTurismo = $o20CounterTurismo + intval($trsmo->tiempo);
                                    }
                                    $trsmo->tiempo = 30 + intval($trsmo->tiempo);
                                    $tCounterTurismo = $tCounterTurismo + intval($trsmo->tiempo);
                                }
                            }
                            $mCounterTurismo = $mCounterTurismo + $trsmo->efectivoRecibido;
                        }
                        $dataObj->turismoTime = $tCounterTurismo;
                        $dataObj->turismoMoney = $mCounterTurismo;
                        $dataObj->turismoOver20kms = $o20CounterTurismo;

                        $pickUp = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                            ->where([
                                'idConductor' => $driver,
                            ])
                            ->whereDate('fechaEntrega', $dataObj->fecha)
                            ->whereHas('delivery', function ($q) {
                                $q->where('idCategoria', 2);
                            });

                        $dataObj->pickup = $pickUp->count();

                        $tCounterPickup = 0;
                        $mCounterPickup = 0;
                        $o20CounterPickup = 0;
                        foreach ($pickUp->get() as $pckup) {
                            if ($pckup->tiempo != null) {
                                if (strpos($pckup->tiempo, 'hour')) {
                                    $stime = explode(' ', $pckup->tiempo);
                                    $time = $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($pckup->distancia) > 20) {
                                        $o20CounterPickup = $o20CounterPickup + intval($time);
                                    }
                                    $pckup->tiempo = 40 + intval($time);
                                    $tCounterPickup = $tCounterPickup + intval($pckup->tiempo);
                                } else {
                                    if (floatval($pckup->distancia) > 20) {
                                        $o20CounterPickup = $o20CounterPickup + intval($pckup->tiempo);
                                    }
                                    $pckup->tiempo = 40 + intval($pckup->tiempo);
                                    $tCounterPickup = $tCounterPickup + intval($pckup->tiempo);
                                }
                            }
                            $mCounterPickup = $mCounterPickup + $pckup->efectivoRecibido;
                        }
                        $dataObj->pickupTime = $tCounterPickup;
                        $dataObj->pickupMoney = $mCounterPickup;
                        $dataObj->pickupOver20kms = $o20CounterPickup;

                        $panel = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                            ->where([
                                'idConductor' => $driver,
                            ])
                            ->whereDate('fechaEntrega', $dataObj->fecha)
                            ->whereHas('delivery', function ($q) {
                                $q->where('idCategoria', 3);
                            });

                        $dataObj->panel = $panel->count();

                        $tCounterPanel = 0;
                        $mCounterPanel = 0;
                        $o20CounterPanel = 0;
                        foreach ($panel->get() as $pnl) {
                            if ($pnl->tiempo != null) {
                                if (strpos($pnl->tiempo, 'hour')) {
                                    $stime = explode(' ', $pnl->tiempo);
                                    $time = $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($pnl->distancia) > 20) {
                                        $o20CounterPanel = $o20CounterPanel + intval($time);
                                    }
                                    $pnl->tiempo = 40 + intval($time);
                                    $tCounterPanel = $tCounterPanel + intval($pnl->tiempo);
                                } else {
                                    if (floatval($pnl->distancia) > 20) {
                                        $o20CounterPanel = $o20CounterPanel + intval($pnl->tiempo);
                                    }
                                    $pnl->tiempo = 40 + intval($pnl->tiempo);
                                    $tCounterPanel = $tCounterPanel + intval($pnl->tiempo);
                                }
                            }
                            $mCounterPanel = $mCounterPanel + $pnl->efectivoRecibido;
                        }
                        $dataObj->panelTime = $tCounterPanel;
                        $dataObj->panelMoney = $mCounterPanel;
                        $dataObj->panelOver20kms = $o20CounterPanel;

                        $pickUpAuxiliar = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                            ->where([
                                'idConductor' => $driver,
                            ])
                            ->whereDate('fechaEntrega', $dataObj->fecha)
                            ->whereHas('delivery', function ($q) {
                                $q->where('idCategoria', 4);
                            });

                        $dataObj->pickupAuxiliar = $pickUpAuxiliar->count();

                        $tCounterPickupAuxiliar = 0;
                        $mCounterPickupAuxiliar = 0;
                        $o20CounterPickupAuxiliar = 0;
                        foreach ($pickUpAuxiliar->get() as $pckAux) {
                            if ($pckAux->tiempo != null) {
                                if (strpos($pckAux->tiempo, 'hour')) {
                                    $stime = explode(' ', $pckAux->tiempo);
                                    $time = $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($pckAux->distancia) > 20) {
                                        $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($time);
                                    }
                                    $pckAux->tiempo = 40 + intval($time);
                                    $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($pckAux->tiempo);
                                } else {
                                    if (floatval($pckAux->distancia) > 20) {
                                        $o20CounterPickupAuxiliar = $o20CounterPickupAuxiliar + intval($pckAux->tiempo);
                                    }
                                    $pckAux->tiempo = 40 + intval($pckAux->tiempo);
                                    $tCounterPickupAuxiliar = $tCounterPickupAuxiliar + intval($pckAux->tiempo);
                                }
                            }
                            $mCounterPickupAuxiliar = $mCounterPickupAuxiliar + $pckAux->efectivoRecibido;
                        }

                        $dataObj->pickupAuxiliarTime = $tCounterPickupAuxiliar;
                        $dataObj->pickupAuxiliarMoney = $mCounterPickupAuxiliar;
                        $dataObj->pickupAuxiliarOver20kms = $o20CounterPickupAuxiliar;

                        $panelAuxiliar = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                            ->where([
                                'idConductor' => $driver,
                            ])
                            ->whereDate('fechaEntrega', $dataObj->fecha)
                            ->whereHas('delivery', function ($q) {
                                $q->where('idCategoria', 5);
                            });

                        $dataObj->panelAuxiliar = $panelAuxiliar->count();

                        $tCounterPanelAuxiliar = 0;
                        $mCounterPanelAuxiliar = 0;
                        $o20CounterPanelAuxiliar = 0;
                        foreach ($panelAuxiliar->get() as $pnlAux) {
                            if ($pnlAux->tiempo != null) {
                                if (strpos($pnlAux->tiempo, 'hour')) {
                                    $stime = explode(' ', $pnlAux->tiempo);
                                    $time = $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($pnlAux->distancia) > 20) {
                                        $o20CounterPanelAuxiliar = $o20CounterPanelAuxiliar + intval($time);
                                    }
                                    $pnlAux->tiempo = 40 + intval($time);
                                    $tCounterPanelAuxiliar = $tCounterPanelAuxiliar + intval($pnlAux->tiempo);
                                } else {
                                    if (floatval($pnlAux->distancia) > 20) {
                                        $o20CounterPanelAuxiliar = $o20CounterPanelAuxiliar + intval($pnlAux->tiempo);
                                    }
                                    $pnlAux->tiempo = 40 + intval($pnlAux->tiempo);
                                    $tCounterPanelAuxiliar = $tCounterPanelAuxiliar + intval($pnlAux->tiempo);
                                }
                            }
                            $mCounterPanelAuxiliar = $mCounterPanelAuxiliar + $pnlAux->efectivoRecibido;
                        }


                        $dataObj->panelAuxiliarTime = $tCounterPanelAuxiliar;
                        $dataObj->panelAuxiliarMoney = $mCounterPickupAuxiliar;
                        $dataObj->panelAuxiliarOver20kms = $o20CounterPanelAuxiliar;

                        $transTurism = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                            ->where([
                                'idConductor' => $driver,
                            ])
                            ->whereDate('fechaEntrega', $dataObj->fecha)
                            ->whereHas('delivery', function ($q) {
                                $q->where('idCategoria', 7);
                            });

                        $dataObj->transTurism = $transTurism->count();

                        $tCounterTransTurism = 0;
                        $mCounterTransTurism = 0;
                        $o20CounterTransTurism = 0;
                        foreach ($transTurism->get() as $tturism) {
                            if ($tturism->tiempo != null) {
                                if (strpos($tturism->tiempo, 'hour')) {
                                    $stime = explode(' ', $tturism->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($tturism->distancia) > 20) {
                                        $o20CounterTransTurism = $o20CounterTransTurism + intval($time);
                                    }
                                    $tturism->tiempo = 20 + intval($time);
                                    $tCounterTransTurism = $tCounterTransTurism + intval($tturism->tiempo);
                                } else {
                                    if (floatval($tturism->distancia) > 20) {
                                        $o20CounterTransTurism = $o20CounterTransTurism + intval($tturism->tiempo);
                                    }
                                    $tturism->tiempo = 20 + intval($tturism->tiempo);
                                    $tCounterTransTurism = $tCounterTransTurism + intval($tturism->tiempo);
                                }
                            }
                            $mCounterTransTurism = $mCounterTransTurism + $tturism->efectivoRecibido;
                        }

                        $dataObj->transTurismTime = $tCounterTransTurism;
                        $dataObj->transTurismMoney = $mCounterTransTurism;
                        $dataObj->transTurismOver20kms = $o20CounterTransTurism;

                        $camion11 = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                            ->where([
                                'idConductor' => $order[$i]->idConductor,
                            ])
                            ->whereDate('fechaEntrega', $dataObj->fecha)
                            ->whereHas('delivery', function ($q) {
                                $q->where('idCategoria', 8);
                            });

                        $dataObj->camion11 = $camion11->count();

                        $tCounterCamion11 = 0;
                        $mCounterCamion11 = 0;
                        $o20CounterCamion11 = 0;
                        foreach ($camion11->get() as $cam11) {
                            if ($cam11->tiempo != null) {
                                if (strpos($cam11->tiempo, 'hour')) {
                                    $stime = explode(' ', $cam11->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($cam11->distancia) > 20) {
                                        $o20CounterCamion11 = $o20CounterCamion11 + intval($time);
                                    }
                                    $cam11->tiempo = 40 + intval($time);
                                    $tCounterCamion11 = $tCounterCamion11 + intval($cam11->tiempo);
                                } else {
                                    if (floatval($cam11->distancia) > 20) {
                                        $o20CounterCamion11 = $o20CounterCamion11 + intval($cam11->tiempo);
                                    }
                                    $cam11->tiempo = 40 + intval($cam11->tiempo);
                                    $tCounterCamion11 = $tCounterCamion11 + intval($cam11->tiempo);
                                }
                            }
                            $mCounterCamion11 = $mCounterCamion11 + $cam11->efectivoRecibido;
                        }

                        $dataObj->camion11Time         = $tCounterCamion11;
                        $dataObj->camion11Money        = $mCounterCamion11;
                        $dataObj->camion11Over20kms    = $o20CounterCamion11;

                        $dataObj->totalOrders = $dataObj->moto + $dataObj->turismo + $dataObj->pickup + $dataObj->panel + $dataObj->pickupAuxiliar + $dataObj->panelAuxiliar + $dataObj->transTurism + $dataObj->camion11;
                        $dataObj->totalTime = $dataObj->motoTime + $dataObj->turismoTime + $dataObj->pickupTime + $dataObj->panelTime + $dataObj->pickupAuxiliarTime + $dataObj->panelAuxiliarTime + $dataObj->transTurismTime + $dataObj->camion11Time;
                        $dataObj->totalMoney = $dataObj->motoMoney + $dataObj->turismoMoney + $dataObj->pickupMoney + $dataObj->panelMoney + $dataObj->pickupAuxiliarMoney + $dataObj->panelAuxiliarMoney + $dataObj->transTurismMoney + $dataObj->camion11Money;
                        $dataObj->totalOver20kms = $dataObj->motoOver20kms + $dataObj->turismoOver20kms + $dataObj->pickupOver20kms + $dataObj->panelOver20kms + $dataObj->pickupAuxiliarOver20kms + $dataObj->panelAuxiliarOver20kms + $dataObj->transTurismOver20kms + $dataObj->camion11Over20kms;

                        $auxTime = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                            ->where([
                                'idAuxiliar' => $order[$i]->idConductor,
                            ])
                            ->whereDate('fechaEntrega', $dataObj->fecha)
                            ->get();

                        $auxCounter = 0;

                        foreach ($auxTime as $aux) {
                            if ($aux->tiempo != null) {
                                if (strpos($aux->tiempo, 'hour')) {
                                    $stime = explode(' ', $aux->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);

                                    $aux->tiempo = 30 + intval($time);
                                    $auxCounter = $auxCounter + intval($aux->tiempo);
                                } else {
                                    $aux->tiempo = 30 + intval($aux->tiempo);
                                    $auxCounter = $auxCounter + intval($aux->tiempo);
                                }
                            }
                        }
                        $dataObj->totalAuxTime = $auxCounter;
                        $dataObj->tiempototal = $dataObj->totalTime + $dataObj->totalOver20kms + $dataObj->totalAuxTime;
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

    //Reporte de envíos
    public function deliveriesReport(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.initDate' => 'required',
            'form.finDate' => 'required'
        ]);

        $form = $request->form;

        $initDate = date('Y-m-d', strtotime($form['initDate']));
        $finDate = date('Y-m-d', strtotime($form['finDate']));
        $isSameDay = $initDate == $finDate;
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

    public function createCustomerDelivery(Request $request)
    {
        $request->validate([
            'deliveryForm' => 'required',
            'deliveryForm.dirRecogida' => 'required',
            'deliveryForm.idCategoria' => 'required',
            'deliveryForm.coordsOrigen' => 'required',
            'deliveryForm.fecha' => 'required',
            'deliveryForm.hora' => 'required',
            'orders' => 'required|array|min:1',
            'pago' => 'required'
        ]);

        if (isset($request->deliveryForm["idTarifa"])) {
            $hDelivery = $request->deliveryForm;
            $deliveryOrders = $request->orders;
            $pago = $request->pago;

            if (sizeof($deliveryOrders) > 0) {
                try {
                    $customerDetails = DeliveryClient::where('idCliente', Auth::user()->idCliente)->get()->first();

                    $nDelivery = new Delivery();
                    $nDelivery->nomCliente = $customerDetails->nomEmpresa;
                    $nDelivery->numIdentificacion = $customerDetails->numIdentificacion;
                    $nDelivery->numCelular = $customerDetails->numTelefono;

                    $date = date('Y-m-d', strtotime($hDelivery['fecha']));
                    $time = date('H:i', strtotime($hDelivery['hora']));

                    $datetime = $date . ' ' . $time;
                    $nDelivery->fechaReserva = new Carbon($datetime);
                    $nDelivery->dirRecogida = $hDelivery['dirRecogida'];
                    $nDelivery->email = $customerDetails->email;
                    $nDelivery->idCategoria = $hDelivery['idCategoria'];
                    $nDelivery->idEstado = 34;
                    $nDelivery->tarifaBase = $pago['baseRate'];
                    $nDelivery->recargos = $pago['recargos'];
                    $nDelivery->cargosExtra = $pago['cargosExtra'];
                    $nDelivery->total = $pago['total'];
                    $nDelivery->idCliente = Auth::user()->idCliente;
                    $nDelivery->coordsOrigen = $hDelivery['coordsOrigen'];
                    $nDelivery->instrucciones = $hDelivery['instrucciones'];
                    $nDelivery->isConsolidada = true;
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
                        $nDetalle->tiempo = $detalle['tiempo'];
                        $nDetalle->tarifaBase = $detalle['tarifaBase'];
                        $nDetalle->recargo = $detalle['recargo'];
                        $nDetalle->cTotal = $detalle['cTotal'];
                        $nDetalle->cargosExtra = $detalle['cargosExtra'];
                        $nDetalle->tomarFoto = true;
                        $nDetalle->instrucciones = $detalle['instrucciones'];
                        $nDetalle->coordsDestino = $detalle['coordsDestino'];
                        $nDetalle->save();

                        if (isset($detalle['extras'])) {

                            foreach ($detalle['extras'] as $exCharge) {
                                $nECOrder = new ExtraChargesOrders();
                                $nECOrder->idDetalle = $nDetalle->idDetalle;
                                $nECOrder->idCargoExtra = $exCharge["idCargoExtra"];
                                $nECOrder->idDetalleOpcion = $exCharge["idDetalleOpcion"];
                                $nECOrder->save();
                            }
                        }
                    }

                    $receivers = $customerDetails->email;
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
                    Log::error($ex->getMessage(), array(
                        'User' => Auth::user()->cliente->nomEmpresa,
                        'context' => $ex->getTrace()
                    ));
                    return response()->json(
                        [
                            'error' => 1,
                            'message' => $ex->getMessage() //'Lo sentimos, ha ocurrido un error al procesar tu solicitud. Por favor intenta de nuevo.'
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
        } else {
            $hDelivery = $request->deliveryForm;
            $deliveryOrders = $request->orders;
            $pago = $request->pago;

            $deliveryDayCode = Carbon::create(date('Y-m-d', strtotime($hDelivery['fecha'])))->dayOfWeek;

            $todaySchedule = Schedule::where('cod', $deliveryDayCode)->where('idTarifaDelivery', null)->get()->first();


            if (
                date('H:i', strtotime($hDelivery['hora'])) < $todaySchedule->inicio ||
                date('H:i', strtotime($hDelivery['hora'])) > $todaySchedule->final
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

            if (sizeof($deliveryOrders) > 0) {
                try {
                    $customerDetails = DeliveryClient::where('idCliente', Auth::user()->idCliente)->get()->first();

                    $nDelivery = new Delivery();
                    $nDelivery->nomCliente = $customerDetails->nomEmpresa;
                    $nDelivery->numIdentificacion = $customerDetails->numIdentificacion;
                    $nDelivery->numCelular = $customerDetails->numTelefono;

                    $date = date('Y-m-d', strtotime($hDelivery['fecha']));
                    $time = date('H:i', strtotime($hDelivery['hora']));

                    $datetime = $date . ' ' . $time;
                    $nDelivery->fechaReserva = new Carbon($datetime);
                    $nDelivery->dirRecogida = $hDelivery['dirRecogida'];
                    $nDelivery->email = $customerDetails->email;
                    $nDelivery->idCategoria = $hDelivery['idCategoria'];
                    $nDelivery->idEstado = 34;
                    $nDelivery->tarifaBase = $pago['baseRate'];
                    $nDelivery->recargos = $pago['recargos'];
                    $nDelivery->cargosExtra = $pago['cargosExtra'];
                    $nDelivery->total = $pago['total'];
                    $nDelivery->idCliente = Auth::user()->idCliente;
                    $nDelivery->coordsOrigen = $hDelivery['coordsOrigen'];
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
                        $nDetalle->tiempo = $detalle['tiempo'];
                        $nDetalle->tarifaBase = $detalle['tarifaBase'];
                        $nDetalle->recargo = $detalle['recargo'];
                        $nDetalle->cTotal = $detalle['cTotal'];
                        $nDetalle->cargosExtra = $detalle['cargosExtra'];
                        $nDetalle->tomarFoto = true;
                        $nDetalle->instrucciones = $detalle['instrucciones'];
                        $nDetalle->coordsDestino = $detalle['coordsDestino'];
                        $nDetalle->save();

                        if (isset($detalle['extras'])) {
                            foreach ($detalle['extras'] as $exCharge) {
                                $nECOrder = new ExtraChargesOrders();
                                $nECOrder->idDetalle = $nDetalle->idDetalle;
                                $nECOrder->idCargoExtra = $exCharge["idCargoExtra"];
                                $nECOrder->idDetalleOpcion = $exCharge["idDetalleOpcion"];
                                $nECOrder->save();
                            }
                        }
                    }

                    $receivers = $customerDetails->email;
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
                    Log::error($ex->getMessage(), array(
                        'User' => Auth::user()->cliente->nomEmpresa,
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
    }

    //CHANGE DELIVERY HOUR

    public function changeDeliveryHour(Request $request)
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
                Auth::user()->idPerfil != 1 && $time < $todaySchedule->inicio || Auth::user()->idPerfil != 1 &&
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
            $deliveriesDia = DetalleDelivery::with(['conductor', 'estado', 'photography'])
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
            $allDeliveries = DetalleDelivery::with(['conductor', 'estado', 'photography'])
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

    public function getTodayCustomerDeliveries()
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

    public function getAllCustomerDeliveries()
    {
        try {
            $user = Auth::user();

            $allDeliveries = Delivery::where('idCliente', $user->idCliente)
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


    /****
     * GET DELIVERIES FUNCTIONS
     ****/


    public function getOrdersByCustomer(Request $request)
    {
        $request->validate(['customerId' => 'required']);
        $custId = $request->customerId;

        try {
            $allDeliveries = DetalleDelivery::with(['estado', 'conductor'])
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
    public function assignDelivery(Request $request)
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

    public function changeStateDelivery(Request $request)
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

    public function assignOrder(Request $request)
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

    public function assignOrderAuxiliar(Request $request)
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

    public function addOrderExtracharge(Request $request)
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

    public function removeOrderExtracharge(Request $request)
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
                    'message' => $ex->getMessage() //'Ocurrió un error al agregar el cargo extra'
                ],
                500
            );
        }
    }

    public function changeOrderState(Request $request)
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

    public function finishDelivery(Request $request)
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

    public function cancelDelivery(Request $request)
    {
        $request->validate(['idDelivery' => 'required', 'motivoAnul' => 'required']);
        $idDelivery = $request->idDelivery;
        $motivoAnul = $request->motivoAnul;

        try {
            $currDelivery = Delivery::where('idDelivery', $idDelivery)->get();
            $currDelivery->idEstado = 36;
            $currDelivery->usrAnuloReserva = Auth::user()->idUsuario;
            $currDelivery->fechaAnulado = Carbon::now();
            $currDelivery->motivoAnul = $motivoAnul;
            $currDelivery->save();

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
                    'message' => 'Ocurrió un error al cancelar la reserva'
                ],
                500
            );
        }
    }

    public function updateDeliveried(Request $request)
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

    public function testContractFormat(Request $request)
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

    public function testSendMail(Request $request)
    {
        $idDelivery = $request->idDelivery;
        $this->sendmail('jylrivera96@gmail.com', $idDelivery);
    }

    /****
     * FUNCTIONS FOR MAIL SENDING
     ****/

    public function sendChangeNotification($mail, $idDelivery)
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

    public function sendmail($mail, $idDelivery)
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
}
