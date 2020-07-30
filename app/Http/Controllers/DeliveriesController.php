<?php

namespace App\Http\Controllers;

use App\Category;
use App\CtrlEstadoDelivery;
use App\Delivery;
use App\DeliveryClient;
use App\DetalleDelivery;
use App\Estado;
use App\Schedule;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DeliveriesController extends Controller
{

    /*********************************
     * FUNCIONES COMPARTIDAS
     ********************************/

    public function getById(Request $request)
    {
        try {
            if (Auth::user()->idPerfil == 1 || Auth::user()->idPerfil == 9) {
                $delivery = Delivery::where('idDelivery', $request->id)->with(['category', 'detalle'])->get()->first();
            } else {
                $delivery = Delivery::where('idCliente', Auth::user()->idCliente)->where('idDelivery', $request->id)
                    ->get()->first();
            }

            $delivery->fechaNoFormatted = $delivery->fechaReserva;
            $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('d/m/Y, h:i a');
            $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
            $delivery->recargos = number_format($delivery->recargos, 2);
            $delivery->total = number_format($delivery->total, 2);
            foreach ($delivery->detalle as $detail) {
                $detail->conductor;
                $detail->estado;
                $detail->tarifaBase = number_format($detail->tarifaBase, 2);
                $detail->recargo = number_format($detail->recargo, 2);
                $detail->cTotal = number_format($detail->cTotal, 2);
            }
            $delivery->estado;

            return response()->json([
                'error' => 0,
                'data' => $delivery],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array(
                    'User' => Auth::user()->nomUsuario,
                    'context' => $ex->getTrace())
            );
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
                $delivery->total = number_format($delivery->total, 2);
            }

            return response()->json([
                'error' => 0,
                'data' => $deliveriesDia
            ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array(
                    'User' => Auth::user()->nomUsuario,
                    'context' => $ex->getTrace())
            );
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
                $delivery->total = number_format($delivery->total, 2);
            }

            return response()->json([
                'error' => 0,
                'data' => $deliveriesTomorrow
            ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array(
                    'User' => Auth::user()->nomUsuario,
                    'context' => $ex->getTrace())
            );
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

            $allDeliveries = Delivery::with(['category', 'detalle', 'estado'])->get();

            foreach ($allDeliveries as $delivery) {
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d H:i');
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->total = number_format($delivery->total, 2);
            }

            return response()->json([
                'error' => 0,
                'data' => $allDeliveries
            ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array(
                    'User' => Auth::user()->nomUsuario,
                    'context' => $ex->getTrace())
            );
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
            return response()->json([
                'error' => 0,
                'data' => $pendingDeliveries],
                500
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array(
                    'User' => Auth::user()->nomUsuario,
                    'context' => $ex->getTrace())
            );
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
            $deliveriesDia = DetalleDelivery::with(['delivery', 'estado', 'conductor'])
                ->whereHas('delivery', function ($q) {
                    $q->whereDate('fechaReserva', Carbon::today());
                })->get();
            $pedidosDia = [];

            foreach ($deliveriesDia as $dtl) {
                $dtl->fechaEntrega = \Carbon\Carbon::parse($dtl->fechaEntrega)->format('Y-m-d H:i');
                $dtl->tarifaBase = number_format($dtl->tarifaBase, 2);
                $dtl->recargo = number_format($dtl->recargo, 2);
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
                    'context' => $ex->getTrace())
            );
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
            $allDeliveries = DetalleDelivery::with(['delivery', 'estado', 'conductor'])->get();
            $todosPedidos = [];

            foreach ($allDeliveries as $dtl) {
                $dtl->fechaEntrega = \Carbon\Carbon::parse($dtl->fechaEntrega)->format('Y-m-d H:i');
                $dtl->tarifaBase = number_format($dtl->tarifaBase, 2);
                $dtl->recargo = number_format($dtl->recargo, 2);
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
                    'context' => $ex->getTrace())
            );
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

        $initDate = date('Y-m-d', strtotime($form['initDate']));
        $finDate = date('Y-m-d', strtotime($form['finDate']));
        $isSameDay = $initDate == $finDate;
        $initDateTime = new Carbon(date('Y-m-d', strtotime($form['initDate'])) . ' 00:00:00');
        $finDateTime = new Carbon(date('Y-m-d', strtotime($form['finDate'])) . ' 23:59:59');

        try {
            $outputData = [];
            $drivers = User::where('idPerfil', 7)->get();

            if ($driver == -1 && $isSameDay) {
                $orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->get()
                    ->groupBy('idConductor');

                foreach ($orders as $order){
                    for($i = 0; $i < sizeof($order); $i ++){
                        $dataObj = (object)array();
                        $dataObj->fecha = Carbon::parse($order[$i]->fechaEntrega)->format('Y-m-d');
                        $dataObj->driver = $order[$i]->conductor->nomUsuario;
                        $dataObj->orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                            ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                            ->where('idConductor', $order[$i]->conductor->idUsuario)->count();

                        if ($dataObj->orders > 0) {
                            $exist = 0;
                            foreach ($outputData as $output) {
                                if ($dataObj->driver == $output->driver) {
                                    $exist++;
                                }
                            }

                            if ($exist == 0) {
                                array_push($outputData, $dataObj);
                            }

                        }
                    }
                }

               /* foreach ($drivers as $driver) {

                    foreach ($orders as $order) {
                        $dataObj = (object)array();
                        $dataObj->fecha = Carbon::parse($order->fechaEntrega)->format('Y-m-d');
                        $dataObj->driver = $driver->nomUsuario;
                        $dataObj->orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                            ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                            ->where('idConductor', $driver->idUsuario)->count();

                        if ($dataObj->orders > 0) {
                            $exist = 0;
                            foreach ($outputData as $output) {
                                if ($dataObj->driver == $output->driver) {
                                    $exist++;
                                }
                            }

                            if ($exist == 0) {
                                array_push($outputData, $dataObj);
                            }

                        }

                    }

                }*/


                return response()->json(
                    [
                        'error' => 0,
                        'data' => $outputData
                    ],
                    200
                );

            } else if ($driver == -1 && !$isSameDay) {
                $orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])->orderBy('fechaEntrega', 'desc')->get()
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
                                $initDateTime = new Carbon(date('Y-m-d', strtotime($dataObj->fecha)) . ' 00:00:00');
                                $finDateTime = new Carbon(date('Y-m-d', strtotime($dataObj->fecha)) . ' 23:59:59');
                                $dataObj->orders = DetalleDelivery::whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                                    ->whereIn('idEstado', [44, 46, 47])
                                    ->where('idConductor', $driver->idUsuario)->count();
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

                return response()->json(
                    [
                        'error' => 0,
                        'data' => $outputData
                    ],
                    200
                );


            } else if ($driver != -1 && $isSameDay) {
                $orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])->where('idConductor', $driver)
                    ->whereDate('fechaEntrega', $initDate)->get();

                if (sizeof($orders) > 0) {
                    foreach ($orders as $order) {
                        $dataObj = (object)array();
                        $dataObj->driver = $driverDetails->nomUsuario;
                        $dataObj->fecha = Carbon::parse($order->fechaEntrega)->format('Y-m-d');
                        $dataObj->orders = sizeof($orders);

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

                return response()->json(
                    [
                        'error' => 0,
                        'data' => $outputData
                    ],
                    200
                );


            } else {
                $orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])->where('idConductor', $driver)
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])->orderBy('fechaEntrega', 'desc')->get()
                    ->groupBy(function ($val) {
                        return Carbon::parse($val->fechaEntrega)->format('Y-m-d');
                    });

                foreach ($orders as $order) {

                    for ($i = 0; $i < sizeof($order); $i++) {
                        $data = (object)array();
                        $data->driver = $driverDetails->nomUsuario;
                        $data->fecha = Carbon::parse($order[$i]->fechaEntrega)->format('Y-m-d');
                        $data->orders = sizeof($order);
                    }
                    array_push($outputData, $data);
                }

                return response()->json(
                    [
                        'error' => 0,
                        'data' => $outputData
                    ],
                    200
                );
            }

        } catch (Exception $ex) {
            return response()->json(
                ['error' => 1,
                    'message' => $ex->getMessage()//'Ocurrió un error al cargar los datos'
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

        $initDate = date('Y-m-d', strtotime($form['initDate']));
        $finDate = date('Y-m-d', strtotime($form['finDate']));
        $isSameDay = $initDate == $finDate;
        $initDateTime = new Carbon(date('Y-m-d', strtotime($form['initDate'])) . ' 00:00:00');
        $finDateTime = new Carbon(date('Y-m-d', strtotime($form['finDate'])) . ' 23:59:59');

        try {
            $outputData = [];
            $customers = DeliveryClient::where('isActivo', 1)->get();

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

                $mydataObj->cTotal = number_format(DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->whereHas('delivery', function ($q) use ($customerDetails, $category) {
                        $q->where('idCliente', $customerDetails->idCliente)->where('idCategoria', $category->idCategoria);
                    })->sum('cTotal'),2);

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


            } else*/ if ($customer != -1 && $isSameDay) {
                $orders = DetalleDelivery::with(['estado','conductor'])->whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->whereHas('delivery', function ($q) use ($customerDetails) {
                        $q->where('idCliente', $customerDetails->idCliente);
                    })->get();

                if (sizeof($orders) > 0) {
                    foreach ($orders as $order) {
                        $order->recargo = number_format($order->recargo,2);
                        $order->cTotal = number_format($order->cTotal,2);
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
                                array_push($outputData, $dataObj);
                            }
                        }


                    }
                }

                return response()->json(
                    [
                        'error' => 0,
                        'data' => array(
                            'ordersReport' => $outputData,
                            'totalOrders' => $totalOrders,
                            'ordersByCategory' => $ordersByCatArray,
                            'orders' => $orders
                        )
                    ],
                    200
                );


            } else if($customer != -1 && !$isSameDay){
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

                $orders = DetalleDelivery::with(['estado','conductor'])->whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->whereHas('delivery', function ($q) use ($customerDetails) {
                        $q->where('idCliente', $customerDetails->idCliente);
                    })->get();
                foreach ($orders as $order){
                    $order->recargo = number_format($order->recargo,2);
                    $order->cTotal = number_format($order->cTotal,2);
                }
                return response()->json(
                    [
                        'error' => 0,
                        'data' => array(
                            'ordersReport' => $outputData,
                            'totalOrders' => $totalOrders,
                            'ordersByCategory' => $ordersByCatArray,
                            'orders' => $orders
                        )
                    ],
                    200
                );
            }

        } catch (Exception $ex) {
            return response()->json(
                ['error' => 1,
                    'message' => $ex->getMessage()],
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

            return response()->json([
                'error' => 0,
                'message' => 'Solicitud de Delivery enviada correctamente.
                    Recibirás un email con los detalles de tu reserva. Nos pondremos en contacto contigo.',
                'nDelivery' => $lastId],
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
            'deliveryForm.fecha' => 'required',
            'deliveryForm.hora' => 'required',
            'deliveryForm.dirRecogida' => 'required',
            'deliveryForm.idCategoria' => 'required',
            'deliveryForm.coordsOrigen' => 'required',
            'orders' => 'required|array|min:1',
            'pago' => 'required'
        ]);

        $hDelivery = $request->deliveryForm;
        $deliveryOrders = $request->orders;
        $pago = $request->pago;

        $deliveryDayCode = Carbon::create(date('Y-m-d', strtotime($hDelivery['fecha'])))->dayOfWeek;

        $todaySchedule = Schedule::where('cod', $deliveryDayCode)->get()->first();


        if (date('H:i', strtotime($hDelivery['hora'])) < $todaySchedule->inicio ||
            date('H:i', strtotime($hDelivery['hora'])) > $todaySchedule->final) {
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
                    $nDetalle->tarifaBase = $detalle['tarifaBase'];
                    $nDetalle->recargo = $detalle['recargo'];
                    $nDetalle->cTotal = $detalle['cTotal'];
                    $nDetalle->instrucciones = $detalle['instrucciones'];
                    $nDetalle->coordsDestino = $detalle['coordsDestino'];
                    $nDetalle->save();
                }

                $receivers = $customerDetails->email;
                $this->sendmail($receivers, $lastId);

                return response()->json([
                    'error' => 0,
                    'message' => 'Solicitud de Delivery enviada correctamente.
                    Recibirás un email con los detalles de tu reserva. Nos pondremos en contacto contigo.',
                    'nDelivery' => $lastId],
                    200
                );

            } catch (Exception $ex) {
                Log::error($ex->getMessage(), array('User' => Auth::user()->cliente->nomEmpresa,
                    'context' => $ex->getTrace()));
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


            if (Auth::user()->idPerfil != 1 && $time < $todaySchedule->inicio || Auth::user()->idPerfil != 1 &&
                $time > $todaySchedule->final) {
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

            return response()->json([
                'error' => 0,
                'message' => 'Hora de recogida actualizada correctamente'],
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
            $deliveriesDia = DetalleDelivery::with(['conductor', 'estado'])
                ->whereHas('delivery', function ($q) use ($user) {
                    $q->whereDate('fechaReserva', Carbon::today())
                        ->where('idCliente', $user->idCliente);
                })->get();
            $pedidosDia = [];


            foreach ($deliveriesDia as $dtl) {
                $dtl->fechaEntrega = \Carbon\Carbon::parse($dtl->fechaEntrega)->format('Y-m-d H:i');
                $dtl->tarifaBase = number_format($dtl->tarifaBase, 2);
                $dtl->recargo = number_format($dtl->recargo, 2);
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
            $allDeliveries = DetalleDelivery::with(['conductor', 'estado'])
                ->whereHas('delivery', function ($q) use ($user) {
                    $q->where('idCliente', $user->idCliente);
                })->get();

            $todosPedidos = [];

            foreach ($allDeliveries as $dtl) {
                $dtl->fechaEntrega = \Carbon\Carbon::parse($dtl->fechaEntrega)->format('Y-m-d H:i');
                $dtl->tarifaBase = number_format($dtl->tarifaBase, 2);
                $dtl->recargo = number_format($dtl->recargo, 2);
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
                //$delivery->fechaReserva = date('d-m-Y h:i', strtotime($delivery->fechaReserva));
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->total = number_format($delivery->total, 2);

            }


            return response()->json([
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
                $delivery->total = number_format($delivery->total, 2);
            }

            return response()->json([
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
            ->whereIn('idEstado',[44,46,47])
                ->whereHas('delivery', function ($q) use ($custId) {
                    $q->where('idCliente', $custId);
                })->get();

            $todosPedidos = [];

            foreach ($allDeliveries as $dtl) {
                $dtl->fechaEntrega = \Carbon\Carbon::parse($dtl->fechaEntrega)->format('Y-m-d H:i');
                $dtl->tarifaBase = number_format($dtl->tarifaBase, 2);
                $dtl->recargo = number_format($dtl->recargo, 2);
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

            $details = DetalleDelivery::where('idDelivery', $idDelivery);
            $details->update(['idEstado' => 37, 'idConductor' => $idConductor]);
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
            $details->update([
                'idEstado' => $stateId,
                'observaciones' => $observ
            ]);
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
            'fechaEntrega' => 'required']);
        $idDetalle = $request->idDetalle;
        $idConductor = $request->idConductor;
        $nomRecibio = $request->nomRecibio;
        $fechaEntrega = new Carbon($request->fechaEntrega);
        try {
            $detail = DetalleDelivery::where('idDetalle', $idDetalle);
            $detail->update(['idConductor' => $idConductor,
                'nomRecibio' => $nomRecibio,
                'fechaEntrega' => $fechaEntrega,
                'entregado' => true]);

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
