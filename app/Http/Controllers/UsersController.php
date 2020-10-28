<?php

namespace App\Http\Controllers;

use App\DetalleDelivery;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Log;

class UsersController extends Controller
{
    public static function existeUsuario($nickName)
    {
        $existeUsuario = User::where('nickUsuario', $nickName)->count();

        return $existeUsuario;
    }

    public static function usuarioActivo($nickName)
    {
        $activo = User::where('nickUsuario', $nickName)->where('isActivo', 1)->get();

        return $activo->count();
    }

    public function listDrivers()
    {
        try {
            $drivers = User::with(['agency', 'agency.city'])->where('isActivo', 1)->where('idPerfil', 7)->get();

            foreach ($drivers as $driver) {
                $driverOrdersPending = DetalleDelivery::where('idEstado', 41)->where('idConductor', $driver->idUsuario)->count();
                $driverOrdersTransit = DetalleDelivery::where('idEstado', 43)->where('idConductor', $driver->idUsuario)->count();

                $driverLLogin = Carbon::parse($driver->lastLogin)->format('Y-m-d');
                if ($driver->lastLogin != null && $driverLLogin == Carbon::today()->format('Y-m-d')) {
                    if ($driverOrdersPending > 0 || $driverOrdersTransit > 0) {
                        $driver->state = 'Ocupado / Entregas: ' . $driverOrdersPending . ' pendiente(s), ' . $driverOrdersTransit . ' en tránsito';
                    } else {
                        $driver->state = 'Disponible';
                    }
                } else {
                    $driver->state = 'No disponible hoy';
                }
            }
            return response()->json(['error' => 0, 'data' => $drivers], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), array('User' => Auth::user()->nomUsuario, 'context' => $ex->getTrace()));
            return response()->json(['error' => 1, 'message' => $ex->getMessage()], 500);
        }
    }

    public function createDriver(Request $request)
    {
        $request->validate(['form' => 'required']);
        try {
            $rDriver = $request->form;
            if (self::existeUsuario($rDriver['nickUsuario']) == 0) {
                $nDriver = new User();
                $nDriver->nomUsuario = $rDriver['nomUsuario'];
                $nDriver->nickUsuario = $rDriver['nickUsuario'];
                $nDriver->idAgencia = $rDriver['idAgencia'];
                $nDriver->passcodeUsuario = $rDriver['passcodeUsuario'];
                $nDriver->numCelular = $rDriver['numCelular'];
                $nDriver->idPerfil = 7;
                $nDriver->fechaCreacion = Carbon::now();
                $nDriver->isActivo = 1;
                $nDriver->save();
                return response()->json(
                    [
                        'error' => 0,
                        'message' => 'Conductor agregado correctamente'
                    ],
                    200
                );
            } else {
                return response()->json(
                    [
                        'error' => 1,
                        'message' => 'Ese nombre de usuario ya está en uso.'
                    ],
                    500
                );
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), array(
                'User' => Auth::user()->nomUsuario,
                'context' => $ex->getTrace()
            ));
            return response()->json(['error' => 1, 'message' => $ex->getMessage()], 500);
        }
    }

    public function updateDriver(Request $request)
    {
        $request->validate(['form' => 'required']);
        try {
            $idDriver = $request->form['idUsuario'];
            $rDriver = $request->form;

            $currentDriver = User::find($idDriver);

            if ($rDriver['nickUsuario'] == $currentDriver->nickUsuario) {
                $currentDriver->update([
                    'nomUsuario' => $rDriver['nomUsuario'],
                    'numCelular' => $rDriver['numCelular'],
                    'idAgencia' => $rDriver['idAgencia'],
                    'passcodeUsuario' => $rDriver['passcodeUsuario']
                ]);

                return response()->json(
                    [
                        'error' => 0,
                        'message' => 'Conductor actualizado correctamente'
                    ],
                    200
                );
            } else {
                if (self::existeUsuario($rDriver['nickUsuario']) == 0) {
                    $currentDriver->update([
                        'nomUsuario' => $rDriver['nomUsuario'],
                        'numCelular' => $rDriver['numCelular'],
                        'nickUsuario' => $rDriver['nickUsuario'],
                        'idAgencia' => $rDriver['idAgencia'],
                        'passcodeUsuario' => $rDriver['passcodeUsuario']
                    ]);
                    return response()->json(
                        [
                            'error' => 0,
                            'message' => 'Conductor actualizado correctamente'
                        ],
                        200
                    );
                } else {
                    return response()->json(
                        [
                            'error' => 1,
                            'message' => 'Ese nombre de usuario ya está en uso.'
                        ],
                        500
                    );
                }
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(['error' => 1, 'message' => $ex->getMessage()], 500);
        }
    }

    public function deliveriesReport(Request $request)
    {
        $request->validate([
            'driverId' => 'required',
        ]);

        $driver = $request->driverId;

        try {
            $outputData = [];
            $orders = DetalleDelivery::with(['delivery'])
                ->whereIn('idEstado', [44, 46, 47])
                ->whereDate('fechaEntrega', Carbon::today())
                ->where('idConductor', $driver)
                ->orWhere('idAuxiliar', $driver)
                ->whereDate('fechaEntrega', Carbon::today())
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

                    switch ($order[$i]->delivery->idCategoria) {
                        case 6:
                            $dataObj->moto++;

                            if ($order[$i]->tiempo != null) {
                                if (strpos($order[$i]->tiempo, 'hour')) {
                                    $stime = explode(' ', $order[$i]->tiempo);
                                    $time = $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order[$i]->distancia) > 20) {
                                        $o20CounterMoto = $o20CounterMoto + intval($time);
                                    }
                                    $order[$i]->tiempo = 30 + intval($time);
                                    $tCounterMoto = $tCounterMoto + intval($order[$i]->tiempo);
                                } else {
                                    if (floatval($order[$i]->distancia) > 20) {
                                        $o20CounterMoto = $o20CounterMoto + intval($order[$i]->tiempo);
                                    }
                                    $order[$i]->tiempo = 30 + intval($order[$i]->tiempo);
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
                                if (strpos($order[$i]->tiempo, 'hour')) {
                                    $stime = explode(" ", $order[$i]->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order[$i]->distancia) > 20) {
                                        $o20CounterTurismo = $o20CounterTurismo + intval($time);
                                    }
                                    $order[$i]->tiempo = 30 + intval($time);
                                    $tCounterTurismo = $tCounterTurismo + intval($order[$i]->tiempo);
                                } else {
                                    if (floatval($order[$i]->distancia) > 20) {
                                        $o20CounterTurismo = $o20CounterTurismo + intval($order[$i]->tiempo);
                                    }
                                    $order[$i]->tiempo = 30 + intval($order[$i]->tiempo);
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
                                if (strpos($order[$i]->tiempo, 'hour')) {
                                    $stime = explode(' ', $order[$i]->tiempo);
                                    $time = $time = intval($stime[0]) * 60 + intval($stime[2]);
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
                                if (strpos($order[$i]->tiempo, 'hour')) {
                                    $stime = explode(' ', $order[$i]->tiempo);
                                    $time = $time = intval($stime[0]) * 60 + intval($stime[2]);
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
                                if (strpos($order[$i]->tiempo, 'hour')) {
                                    $stime = explode(' ', $order[$i]->tiempo);
                                    $time = $time = intval($stime[0]) * 60 + intval($stime[2]);
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
                            }
                            $mCounterPickupAuxiliar = $mCounterPickupAuxiliar + $order[$i]->efectivoRecibido;

                            $dataObj->pickupAuxiliarTime += $tCounterPickupAuxiliar;
                            $dataObj->pickupAuxiliarMoney += $mCounterPickupAuxiliar;
                            $dataObj->pickupAuxiliarOver20kms += $o20CounterPickupAuxiliar;
                            break;
                        case 5:
                            $dataObj->panelAuxiliar++;

                            if ($order[$i]->tiempo != null && $order[$i]->idAuxiliar != $driver) {
                                if (strpos($order[$i]->tiempo, 'hour')) {
                                    $stime = explode(' ', $order[$i]->tiempo);
                                    $time = $time = intval($stime[0]) * 60 + intval($stime[2]);
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
                            }
                            $mCounterPanelAuxiliar = $mCounterPanelAuxiliar + $order[$i]->efectivoRecibido;

                            $dataObj->panelAuxiliarTime += $tCounterPanelAuxiliar;
                            $dataObj->panelAuxiliarMoney += $mCounterPanelAuxiliar;
                            $dataObj->panelAuxiliarOver20kms += $o20CounterPanelAuxiliar;
                            break;
                        case 7:
                            $dataObj->transTurism++;

                            if ($order[$i]->tiempo != null) {
                                if (strpos($order[$i]->tiempo, 'hour')) {
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
                                if (strpos($order[$i]->tiempo, 'hour')) {
                                    $stime = explode(' ', $order[$i]->tiempo);
                                    $time = intval($stime[0]) * 60 + intval($stime[2]);
                                    if (floatval($order[$i]->distancia) > 20) {
                                        $o20CounterCamion11 = $o20CounterCamion11 + intval($time);
                                    }
                                    $order[$i]->tiempo = 40 + intval($time);
                                    $tCounterCamion11 = $tCounterCamion11 + intval($order[$i]->tiempo);
                                } else {
                                    if (floatval($order[$i]->distancia) > 20) {
                                        $o20CounterCamion11 = $o20CounterCamion11 + intval($order[$i]->tiempo);
                                    }
                                    $order[$i]->tiempo = 40 + intval($order[$i]->tiempo);
                                    $tCounterCamion11 = $tCounterCamion11 + intval($order[$i]->tiempo);
                                }
                            }
                            $mCounterCamion11 = $mCounterCamion11 + $order[$i]->efectivoRecibido;

                            $dataObj->camion11Time += $tCounterCamion11;
                            $dataObj->camion11Money += $mCounterCamion11;
                            $dataObj->camion11Over20kms += $o20CounterCamion11;
                            break;
                    }

                    $dataObj->totalOrders = $dataObj->moto + $dataObj->turismo + $dataObj->pickup + $dataObj->panel + $dataObj->pickupAuxiliar + $dataObj->panelAuxiliar + $dataObj->transTurism + $dataObj->camion11;
                    $dataObj->totalTime = $dataObj->motoTime + $dataObj->turismoTime + $dataObj->pickupTime + $dataObj->panelTime + $dataObj->pickupAuxiliarTime + $dataObj->panelAuxiliarTime + $dataObj->transTurismTime + $dataObj->camion11Time;
                    $dataObj->totalMoney = $dataObj->motoMoney + $dataObj->turismoMoney + $dataObj->pickupMoney + $dataObj->panelMoney + $dataObj->pickupAuxiliarMoney + $dataObj->panelAuxiliarMoney + $dataObj->transTurismMoney + $dataObj->camion11Money;
                    $dataObj->totalOver20kms = $dataObj->motoOver20kms + $dataObj->turismoOver20kms + $dataObj->pickupOver20kms + $dataObj->panelOver20kms + $dataObj->pickupAuxiliarOver20kms + $dataObj->panelAuxiliarOver20kms + $dataObj->transTurismOver20kms + $dataObj->camion11Over20kms;

                    $auxTime = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                        ->where([
                            'idAuxiliar' => $driver,
                        ])
                        ->whereHas('delivery', function ($q) {
                            $q->whereIn('idCategoria', [4, 5, 8]);
                        })
                        ->whereDate('fechaEntrega', $dataObj->fecha)
                        ->get();

                    $auxCounter = 0;

                    foreach ($auxTime as $aux) {
                        if ($aux->tiempo != null) {
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
                    $dataObj->totalAuxTime = $auxCounter;

                    $extTime = DetalleDelivery::with('extraCharges')
                        ->whereIn('idEstado', [44, 46, 47])
                        ->where([
                            'idConductor' => $driver,
                        ])
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
}
