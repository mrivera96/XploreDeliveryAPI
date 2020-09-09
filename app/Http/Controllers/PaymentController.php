<?php

namespace App\Http\Controllers;

use App\Delivery;
use App\Payment;
use App\PaymentType;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function createPayment(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.fechaPago' => 'required',
            'form.monto' => 'required',
            'form.tipoPago' => 'required',
            'form.idCliente' => 'required',
        ]);

        $rPayment = $request->form;

        try {
            $finishedDeliveries = Delivery::where('idCliente', $rPayment['idCliente'])->where('idEstado', 39)->get();

            if (sizeof($finishedDeliveries) == 0) {
                return response()->json(
                    [
                        'error' => 1,
                        'message' => 'El cliente seleccionado no tiene entregas finalizadas'
                    ],
                    500
                );
            }

            $subtotal = Delivery::where('idCliente', $rPayment['idCliente'])->where('idEstado', 39)->sum('total');

            $paid = Payment::where('idCliente', $rPayment['idCliente'])->sum('monto');

            $balance = doubleval($subtotal) - doubleval($paid);

            $nBalance = $balance - $rPayment['monto'];
            /*if($nBalance < 0){
                return response()->json(
                    [
                        'error' => 1,
                        'message' => 'El pago que intenta registrar produce un saldo negativo'
                    ],
                    500
                );
            }*/

            $nPayment = new Payment();
            $nPayment->idUsuario = Auth::user()->idUsuario;
            $nPayment->fechaPago = date('Y-m-d', strtotime($rPayment['fechaPago']));
            $nPayment->monto = $rPayment['monto'];
            $nPayment->tipoPago = $rPayment['tipoPago'];
            $nPayment->idCliente = $rPayment['idCliente'];
            $nPayment->fechaRegistro = Carbon::now();
            if ($rPayment['tipoPago'] == 6) {
                $existe = Payment::where('idCliente', $nPayment->idCliente)
                    ->where('numAutorizacion', $rPayment['numAutorizacion'])->count();

                if ($existe == 0) {
                    $nPayment->numAutorizacion = $rPayment['numAutorizacion'];
                } else {
                    return response()->json(
                        [
                            'error' => 1,
                            'message' => 'Ese número de autorización ya está registrado'
                        ],
                        500
                    );
                }

            } else if ($rPayment['tipoPago'] == 7) {
                $existe = Payment::where('idCliente', $nPayment->idCliente)
                    ->where('referencia', $rPayment['referencia'])->count();
                if ($existe == 0) {
                    $nPayment->referencia = $rPayment['referencia'];
                    $nPayment->banco = $rPayment['banco'];
                }else {
                    return response()->json(
                        [
                            'error' => 1,
                            'message' => 'Ese número de referencia ya está registrado'
                        ],
                        500
                    );
                }

            }

            $nPayment->save();

            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Se ha registrado el pago correctamente'
                ],
                200
            );


        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array([
                'User' => Auth::user()->nomUsuario,
                'context' => $ex->getTrace()
            ]));

            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
        }
    }

    public function getPayments()
    {
        try {
            $payments = Payment::with(['customer', 'user', 'paymentType'])->get();

            foreach ($payments as $payment) {
                $payment->fechaPago = Carbon::parse($payment->fechaPago)->format('Y-m-d');
                $payment->monto = number_format($payment->monto, 2);
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => $payments
                ],
                200
            );

        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array([
                'User' => Auth::user()->nomUsuario,
                'context' => $ex->getTrace()
            ]));

            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
        }
    }

    public function getPaymentsReport(Request $request)
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
            $payments = Payment::with(['customer', 'paymentType'])
                ->whereBetween('fechaPago',[$initDateTime, $finDateTime])
                ->get();

            foreach ($payments as $payment) {
                $payment->fechaPago = Carbon::parse($payment->fechaPago)->format('Y-m-d');
                $payment->monto = number_format($payment->monto, 2);
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => $payments
                ],
                200
            );

        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array([
                'User' => Auth::user()->nomUsuario,
                'context' => $ex->getTrace()
            ]));

            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
        }
    }

    public function getPaymentTypes()
    {
        try {
            $paymentTypes = PaymentType::where('isActivo', 1)->whereIn('idTipoPago', [6, 7, 8])->get();
            return response()->json(
                [
                    'error' => 0,
                    'data' => $paymentTypes
                ],
                200
            );

        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array([
                'User' => Auth::user()->nomUsuario,
                'context' => $ex->getTrace()
            ]));

            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
        }
    }
}
