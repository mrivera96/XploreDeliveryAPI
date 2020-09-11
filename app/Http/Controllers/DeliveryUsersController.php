<?php

namespace App\Http\Controllers;

use App\DeliveryClient;
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

class DeliveryUsersController extends Controller
{
    public function list()
    {
        try {
            $customers = DeliveryClient::where('isActivo', 1)->get();

            return response()
                ->json([
                    'error' => 0,
                    'data' => $customers
                ], 200);
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), array('User' => Auth::user()->nomUsuario, 'context' => $exception->getTrace()));
            return response()
                ->json([
                    'error' => 1,
                    'message' => 'Ocurrió un error al cargar los datos'
                ], 500);
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
                'message' => $exception->getMessage()
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
                $nCustomer->isActivo = 1;
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
                'message' => $exception->getMessage()
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
                    'numTelefono' => $rCustomer['numTelefono'],
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
                'User' => Auth::user()->nomUsuario,
                'context' => $exception->getTrace()
            ));
            return response()->json([
                'error' => 1,
                'message' => $exception->getMessage()
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


            return response()->json([
                'error' => 0,
                'finishedOrdersCount' => number_format($finishedOrders->count()),
                'actualBalance' => number_format($actualBalance, 2),
                'pendingOrdersCount' => $pendingOrders->count(),
                'pendingOrders' => $pendingOrders->get(),
                'assignedOrdersCount' => $assignedOrders->count(),
                'assignedOrders' => $assignedOrders->get()
            ]);
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array(
                'User' => Auth::user()->nomUsuario,
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

        $initDate = date('Y-m-d', strtotime($form['initDate']));
        $finDate = date('Y-m-d', strtotime($form['finDate']));
        $initDateTime = new Carbon(date('Y-m-d', strtotime($form['initDate'])) . ' 00:00:00');
        $finDateTime = new Carbon(date('Y-m-d', strtotime($form['finDate'])) . ' 23:59:59');

        try {
            $outputData = [];
            $customers = DeliveryClient::where('isActivo', 1)->get();

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

                if ($dataObj->orders > 0) {
                    array_push($outputData, $dataObj);
                }
            }

            $totalOrders = 0;
            $totalPayments = 0;
            $totalBalance = 0;

            foreach ($outputData as $output) {
                $totalOrders += $output->orders;
                $totalPayments += $output->payments;
                $totalBalance += $output->balance;
            }
            return response()->json(
                [
                    'error' => 0,
                    'data' => $outputData,
                    'totalOrders' => $totalOrders,
                    'totalPayments' => number_format($totalPayments,2),
                    'totalBalance' => number_format($totalBalance,2)
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
