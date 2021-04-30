<?php

namespace App\Http\Controllers;

use App\DeliveryClient;
use App\DeliveryCustomerWorkLines;
use App\DetalleDelivery;
use App\User;
use App\Payment;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Tarifa;

class DeliveryUsersController extends Controller
{
    public function list()
    {
        try {
            $customers = DeliveryClient::where('isActivo', 1)
                ->orderBy('nomEmpresa', 'ASC')
                ->get();

            return response()
                ->json([
                    'error' => 0,
                    'data' => $customers
                ], 200);
        } catch (Exception $exception) {
            Log::error(
                $exception->getMessage(),
                array(
                    'User' => Auth::user()->nomUsuario,
                    'context' => $exception->getTrace()
                )
            );
            return response()
                ->json([
                    'error' => 1,
                    'message' => 'Ocurrió un error al cargar los datos'
                ], 500);
        }
    }

    public function getCustomerWorkLines(Request $request)
    {
        try {
            $request->validate([
                'customerId' => 'required'
            ]);

            $customerWL = DeliveryCustomerWorkLines::with('workLine')
                ->where('idCliente', $request->customerId)
                ->get();

            return response()
                ->json([
                    'error' => 0,
                    'data' => $customerWL
                ], 200);
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

    public function changePassword(Request $request)
    {
        $changePassForm = $request->form;
        try {

            $currUser = User::where('idUsuario', Auth::user()->idUsuario)->get()->first();

            if (Auth::user()->passUsuario == utf8_encode($this->obtenerCifrado($changePassForm['oldPass'])) || Hash::check($changePassForm['oldPass'], Auth::user()->passUsuario)) {
                $newPass = Hash::make($changePassForm['newPass']);
                $currUser->passUsuario = $newPass;
                $currUser->save();

                return response()->json([
                    'error' => 0,
                    'message' => 'Contraseña actualizada correctamente.'
                ], 200);
            } else {

                return response()->json([
                    'error' => 1,
                    'message' => 'La contraseña actual ingresada no coincide con nuestros registros.'
                ], 500);
            }
        } catch (Exception $exception) {
            Log::error(
                $exception->getMessage(),
                array('User' => Auth::user()->nomUsuario, 'context' => $exception->getTrace())
            );
            return response()->json([
                'error' => 1,
                'message' => 'Ocurrió un error' //$exception->getMessage()
            ], 500);
        }
    }

    public function newCustomer(Request $request)
    {
        try {
            $rCustomer = $request->form;

            if (UsersController::existeUsuario($rCustomer['email']) == 0) {
                $nCustomer = new DeliveryClient();
                $nCustomer->nomEmpresa = $rCustomer['nomEmpresa'];
                $nCustomer->nomRepresentante = $rCustomer['nomRepresentante'];
                $nCustomer->numIdentificacion = $rCustomer['numIdentificacion'];
                $nCustomer->numTelefono = $rCustomer['numTelefono'];
                $nCustomer->email = $rCustomer['email'];
                $nCustomer->enviarNotificaciones = $rCustomer['enviarNotificaciones'];
                $nCustomer->isActivo = 1;
                $nCustomer->montoGracia = $rCustomer['montoGracia'];
                $nCustomer->fechaAlta = Carbon::now();
                $nCustomer->save();

                $nUser = new User();
                $nUser->idPerfil = 8;
                $nUser->nomUsuario = $rCustomer['nomRepresentante'];
                $nUser->nickUsuario = $rCustomer['email'];
                $nUser->passUsuario = Hash::make($rCustomer['numIdentificacion']);
                $nUser->isActivo = 1;
                $nUser->idCliente = $nCustomer->idCliente;
                $nUser->fechaCreacion = Carbon::now();
                $nUser->save();

                $receivers = $nCustomer->email;

                $this->sendmail($receivers, $nCustomer);
                return response()->json([
                    'error' => 0,
                    'message' => 'Cliente agregado correctamente.'
                ], 200);
            } else {
                return response()->json([
                    'error' => 1,
                    'message' => 'Ya existe este usuario.'
                ], 500);
            }
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), array(
                'User' => Auth::user()->nomUsuario,
                'context' => $exception->getTrace()
            ));
            return response()->json([
                'error' => 1,
                'message' => 'Ocurrió un error al agregar el cliente' //$exception->getMessage()
            ], 500);
        }
    }

    public function updateCustomer(Request $request)
    {
        try {
            $rCustomer = $request->form;
            $currCustomer = DeliveryClient::find($rCustomer['idCliente']);
            $currUser = User::where('idCliente', $rCustomer['idCliente']);

            if ($rCustomer['email'] == $currCustomer->email) {
                $currCustomer->update([
                    'nomEmpresa' => $rCustomer['nomEmpresa'],
                    'nomRepresentante' => $rCustomer['nomRepresentante'],
                    'numIdentificacion' => $rCustomer['numIdentificacion'],
                    'enviarNotificaciones' => $rCustomer['enviarNotificaciones'],
                    'numTelefono' => $rCustomer['numTelefono'],
                    'montoGracia' => $rCustomer['montoGracia']
                ]);

                $currUser->update([
                    'nomUsuario' => $rCustomer['nomRepresentante'],
                ]);

                return response()->json([
                    'error' => 0,
                    'message' => 'Cliente actualizado correctamente.'
                ], 200);
            } else {
                if (UsersController::existeUsuario($rCustomer['email']) == 0) {
                    $currCustomer->update([
                        'nomEmpresa' => $rCustomer['nomEmpresa'],
                        'nomRepresentante' => $rCustomer['nomRepresentante'],
                        'numIdentificacion' => $rCustomer['numIdentificacion'],
                        'numTelefono' => $rCustomer['numTelefono'],
                        'email' => $rCustomer['email'],
                        'enviarNotificaciones' => $rCustomer['enviarNotificaciones'],
                        'montoGracia' => $rCustomer['montoGracia']
                    ]);

                    $currUser->update([
                        'nomUsuario' => $rCustomer['nomRepresentante'],
                        'nickUsuario' => $rCustomer['email'],
                    ]);

                    return response()->json([
                        'error' => 0,
                        'message' => 'Cliente actualizado correctamente.'
                    ], 200);
                } else {
                    return response()->json([
                        'error' => 1,
                        'message' => 'Ya existe este usuario.'
                    ], 500);
                }
            }
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), array(
                'context' => $exception->getTrace()
            ));
            return response()->json([
                'error' => 1,
                'message' => 'Ocurrió un error al actualizar el cliente' //$exception->getMessage()
            ], 500);
        }
    }

    public function changePhotoInstructions(Request $request)
    {
        $request->validate([
            'form'=>'required',
            'form.instFotografias'=>'required']);

        $instructions = $request->form['instFotografias'];
        $customer = Auth::user()->idCliente;

        try {
            DeliveryClient::where('idCliente', $customer)
                ->update(['instFotografias'=>$instructions]);

            return response()->json([
                'error' => 0,
                'message' => 'Instrucciones de Fotografías actualizadas correctamente.'
            ], 200);

        } catch (Exception $exception) {
            Log::error($exception->getMessage(), array(
                'context' => $exception->getTrace()
            ));
            return response()->json([
                'error' => 1,
                'message' => 'Ocurrió un error al actualizar las instrucciones' //$exception->getMessage()
            ], 500);
        }
    }

    public function sendmail($mail, $cliente)
    {
        $data["email"] = $mail;
        $data["client_name"] = $cliente->Representante;
        $data["subject"] = 'Detalles de Acceso Xplore Delivery';
        $data["cliente"] = $cliente;
        $data["from"] = 'Xplore Delivery';

        try {
            Mail::send('userCredentials', $data, function ($message) use ($data) {
                $message
                    ->from('noreply@xplorerentacar.com', $data["from"])
                    ->to($data["email"], $data["client_name"])
                    ->subject($data["subject"]);
            });
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), ['context' => $exception->getTrace()]);
            $this->serverstatuscode = "0";
            $this->serverstatusdes = $exception->getMessage();
        }
    }

    private function obtenerCifrado($psswd)
    {
        $httpClient = new Client();
        $res = $httpClient->get('https://appconductores.xplorerentacar.com/mod.ajax/encriptar.php?password=' . $psswd);
        return json_decode($res->getBody());
    }

    public function testAccessDetails(Request $request)
    {
        $cliente = DeliveryClient::where('idCliente', $request->idCliente)->get()->first();
        return view('mails.userCredentials', compact('cliente'));
    }

    public function dashboardData()
    {
        try {
            $finishedOrders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                ->whereHas('delivery', function ($q) {
                    $q->where('idCliente', Auth::user()->idCliente);
                });

            $pendingOrders = DetalleDelivery::with('delivery')->where('idEstado', 34)
                ->whereHas('delivery', function ($q) {
                    $q->where('idCliente', Auth::user()->idCliente);
                });

            $assignedOrders = DetalleDelivery::with('conductor')->whereIn('idEstado', [41, 43])
                ->whereHas('delivery', function ($q) {
                    $q->where('idCliente', Auth::user()->idCliente);
                });

            $subtotal = $finishedOrders->sum('cTotal');
            $paid = Payment::where('idCliente', Auth::user()->idCliente)
                ->sum('monto');
            $actualBalance = $subtotal - $paid;

            foreach ($pendingOrders->get() as $order){
                $order->delivery->fechaReserva = \Carbon\Carbon::parse($order->delivery->fechaReserva)->format('d/m/Y, h:i a');
            }

            return response()->json([
                'error' => 0,
                'finishedOrdersCount' => $finishedOrders->count(),
                'actualBalance' => number_format($actualBalance,2),
                'pendingOrdersCount' => $pendingOrders->count(),
                'pendingOrders' => $pendingOrders->get(),
                'assignedOrdersCount' => $assignedOrders->count(),
                'assignedOrders' => $assignedOrders->get()
            ]);
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array(
                'context' => $ex->getTrace()
            ));

            return response()->json([
                'error' => 1,
                'message' => 'Ha ocurrido un error al cargar sus datos'
            ], 500);
        }
    }

    public function customerBalance(Request $request)
    {
        $request->validate(['idCliente' => 'required']);
        $customerId = $request->idCliente;
        try {

            $finishedOrders = DetalleDelivery::with(['estado', 'conductor'])
                ->whereIn('idEstado', [44, 46, 47])
                ->whereHas('delivery', function ($q) use ($customerId) {
                    $q->where('idCliente', $customerId);
                });

            $payments = Payment::with('paymentType')->where('idCliente', $customerId);

            $subtotal = $finishedOrders->sum('cTotal');
            $paid = $payments->sum('monto');
            $balance = $subtotal - $paid;

            $finishedOrdersGet = $finishedOrders->get();

            foreach ($finishedOrdersGet as $detail) {
                $detail->fechaEntrega = \Carbon\Carbon::parse($detail->fechaEntrega)->format('Y-m-d H:i');
                $detail->tarifaBase = number_format($detail->tarifaBase, 2);
                $detail->recargo = number_format($detail->recargo, 2);
                $detail->cargosExtra = number_format($detail->cargosExtra, 2);
                $detail->cTotal = number_format($detail->cTotal, 2);
            }

            $paymentsGet = $payments->get();

            foreach ($paymentsGet as $payment) {
                $payment->monto = number_format($payment->monto, 2);
            }

            return response()->json(
                [
                    'error' => 0,
                    'finishedOrders' => $finishedOrdersGet,
                    'footSurcharges' => number_format($finishedOrders->sum('recargo'), 2),
                    'footExtraCharges' => number_format($finishedOrders->sum('cargosExtra'), 2),
                    'footCTotal' => number_format($finishedOrders->sum('cTotal'), 2),
                    'footMonto' => number_format($payments->sum('monto'), 2),
                    'subtotal' => number_format($subtotal, 2),
                    'paid' => number_format($paid, 2),
                    'balance' => number_format($balance, 2),
                    'payments' => $paymentsGet
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

    public function getCustomersBalanceReport(Request $request)
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
            $customers = DeliveryClient::where('isActivo', 1)->get();

            $totalOrders = 0;
            $totalPayments = 0;
            $totalBalance = 0;
            $totalCredit = 0;

            foreach ($customers as $customer) {
                $dataObj = (object)array();
                $dataObj->customer = $customer;
                $dataObj->orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->whereHas('delivery', function ($q) use ($customer) {
                        $q->where('idCliente', $customer->idCliente);
                    })->count();
                $dataObj->payments = number_format(Payment::where('idCliente', $customer->idCliente)
                    ->whereBetween('fechaPago', [$initDateTime, $finDateTime])
                    ->sum('monto'), 2);
                $dataObj->balance = number_format(DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                        ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                        ->whereHas('delivery', function ($q) use ($customer) {
                            $q->where('idCliente', $customer->idCliente);
                        })->sum('cTotal') - Payment::where('idCliente', $customer->idCliente)
                        ->whereBetween('fechaPago', [$initDateTime, $finDateTime])
                        ->sum('monto'), 2);
                $dataObj->credit = number_format($customer->montoGracia - (DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                            ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                            ->whereHas('delivery', function ($q) use ($customer) {
                                $q->where('idCliente', $customer->idCliente);
                            })->sum('cTotal') - Payment::where('idCliente', $customer->idCliente)
                            ->whereBetween('fechaPago', [$initDateTime, $finDateTime])
                            ->sum('monto')), 2);

                if ($dataObj->orders > 0) {
                    array_push($outputData, $dataObj);
                    $totalOrders += $dataObj->orders;
                    $totalPayments += Payment::where('idCliente', $customer->idCliente)
                        ->whereBetween('fechaPago', [$initDateTime, $finDateTime])
                        ->sum('monto');
                    $totalBalance += DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                            ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                            ->whereHas('delivery', function ($q) use ($customer) {
                                $q->where('idCliente', $customer->idCliente);
                            })->sum('cTotal') - Payment::where('idCliente', $customer->idCliente)
                            ->whereBetween('fechaPago', [$initDateTime, $finDateTime])
                            ->sum('monto');
                    $totalCredit += $customer->montoGracia - (DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                                ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                                ->whereHas('delivery', function ($q) use ($customer) {
                                    $q->where('idCliente', $customer->idCliente);
                                })->sum('cTotal') - Payment::where('idCliente', $customer->idCliente)
                                ->whereBetween('fechaPago', [$initDateTime, $finDateTime])
                                ->sum('monto'));
                }
            }


            return response()->json(
                [
                    'error' => 0,
                    'data' => $outputData,
                    'totalOrders' => $totalOrders,
                    'totalPayments' => number_format($totalPayments, 2),
                    'totalBalance' => number_format($totalBalance, 2),
                    'totalCredit' => number_format($totalCredit, 2)
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

    public function getCustomersTrackingReport(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.numMinEnvios' => 'required',
            'form.initDate' => 'required',
            'form.finDate' => 'required',
            'form.initDateWO' => 'required',
            'form.finDateWO' => 'required',
        ]);

        try {
            $form = $request->form;
            $minOrders = $form['numMinEnvios'];

            $initDateTime = new Carbon(date('Y-m-d', strtotime($form['initDate'])) . ' 00:00:00');
            $finDateTime = new Carbon(date('Y-m-d', strtotime($form['finDate'])) . ' 23:59:59');

            $initDateTimeWO = new Carbon(date('Y-m-d', strtotime($form['initDateWO'])) . ' 00:00:00');
            $finDateTimeWO = new Carbon(date('Y-m-d', strtotime($form['finDateWO'])) . ' 23:59:59');

            /*$orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime]);*/

            $customersArray = DeliveryClient::whereHas('deliveries', function ($q) use ($initDateTime, $finDateTime) {
                $q->whereHas('detalle', function ($q) use ($initDateTime, $finDateTime) {
                    $q->whereBetween('fechaEntrega', [$initDateTime, $finDateTime]);
                });
            })->get();

            $finalCustomersArray = [];

            foreach ($customersArray as $customer) {
                $mydataObj = (object)array();
                $orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                    ->whereBetween('fechaEntrega', [$initDateTimeWO, $finDateTimeWO])
                    ->whereHas('delivery', function ($q) use ($customer) {
                        $q->where('idCliente', $customer->idCliente);
                    })->count();

                if ($orders < $minOrders) {
                    $mydataObj->customer = $customer;
                    $mydataObj->lastOrder = Carbon::parse(DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                        ->whereHas('delivery', function ($q) use ($customer) {
                            $q->where('idCliente', $customer->idCliente);
                        })->max('fechaEntrega'))->format('Y-m-d');
                    array_push($finalCustomersArray, $mydataObj);
                }
            }

            /*if ($orders->count() >= $minOrders) {
                $customersArray = [];
                foreach ($orders->get() as $order) {
                    if (!in_array($order->delivery->cliente, $customersArray)) {
                        array_push($customersArray, $order->delivery->cliente);
                    }
                }

                $finalCustomersArray = [];

                foreach ($customersArray as $customer) {
                    $mydataObj = (object)array();
                    $orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                        ->whereBetween('fechaEntrega', [$initDateTimeWO, $finDateTimeWO])
                        ->whereHas('delivery', function ($q) use ($customer) {
                            $q->where('idCliente', $customer->idCliente);
                        })->count();

                    if ($orders == 0) {
                        $mydataObj->customer = $customer;
                        $mydataObj->lastOrder = Carbon::parse(DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                            ->whereHas('delivery', function ($q) use ($customer) {
                                $q->where('idCliente', $customer->idCliente);
                            })->max('fechaEntrega'))->format('Y-m-d');
                        array_push($finalCustomersArray, $mydataObj);
                    }

                }*/

            return response()
                ->json([
                    'data' => $finalCustomersArray
                ]);
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

    public function checkAvalability(Request $request)
    {
        try {
            $customer = Auth::user()->idCliente;
            $output = true;
            /*$orders = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                ->whereHas('delivery', function ($q) use ($customer) {
                    $q->where('idCliente', $customer);
                })->count();*/
            $payments = Payment::where('idCliente', $customer)
                ->max('fechaPago');

            $balance = number_format(DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                    ->whereHas('delivery', function ($q) use ($customer) {
                        $q->where('idCliente', $customer);
                    })->sum('cTotal') - Payment::where('idCliente', $customer)
                    ->sum('monto'), 2);

            if ($balance > 0) {
                $graceAmount = Auth::user()->cliente->montoGracia;
                $balNonFormated = DetalleDelivery::whereIn('idEstado', [44, 46, 47])
                        ->whereHas('delivery', function ($q) use ($customer) {
                            $q->where('idCliente', $customer);
                        })->sum('cTotal') - Payment::where('idCliente', $customer)
                        ->sum('monto');
                $dif = $balNonFormated - $graceAmount;

                if ($dif >= 0) {
                    $output = false;
                }
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => $output,
                    'balance' => $balance
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

    public function checkCustomerDelTypes(Request $request)
    {
        try {
            $customer = $request->customerId;

            $custConsolidatedRates = Tarifa::where('idTipoTarifa', 2)
                ->whereHas('rateDetail', function ($q) use ($customer) {
                    $q->where('idCliente', $customer);
                })->count();
            $custForConsolidatedRates = Tarifa::where('idTipoTarifa', 4)
                ->whereHas('rateDetail', function ($q) use ($customer) {
                    $q->where('idCliente', $customer);
                })->count();

            $hasConsolidatedRate = false;
            if ($custConsolidatedRates > 0) {
                $hasConsolidatedRate = true;
            }

            $hasFConsolidatedRate = false;
            if ($custForConsolidatedRates > 0) {
                $hasFConsolidatedRate = true;
            }

            return response()->json([
                'error' => 0,
                'data' => array('consolidated' => $hasConsolidatedRate, 'foreign' => $hasFConsolidatedRate)
            ]);
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
}
