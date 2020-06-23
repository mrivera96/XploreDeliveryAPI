<?php

namespace App\Http\Controllers;

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
            $nPayment = new Payment();
            $nPayment->idUsuario = Auth::user()->idUsuario;
            $nPayment->fechaPago = $rPayment['fechaPago'];
            $nPayment->monto = $rPayment['monto'];
            $nPayment->tipoPago = $rPayment['tipoPago'];
            $nPayment->idCliente = $rPayment['idCliente'];
            $nPayment->fechaRegistro = Carbon::now();
            if($rPayment['tipoPago'] == 6 || $rPayment['tipoPago'] == 7){
                $nPayment->referencia = $rPayment['referencia'];
                $nPayment->banco = $rPayment['banco'];
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

    public function getPayments(){
        try {
            $payments = Payment::all();

            foreach ($payments as $payment){
                $payment->customer;
                $payment->user;
                $payment->paymentType;
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => $payments
                ],
                200
            );

        }catch (Exception $ex){
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

    public function getPaymentTypes(){
        try {
            $paymentTypes = PaymentType::where('isActivo',1)->whereIn('idTipoPago',[6,7,8])->get();
            return response()->json(
                [
                    'error' => 0,
                    'data' => $paymentTypes
                ],
                200
            );

        }catch (Exception $ex){
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