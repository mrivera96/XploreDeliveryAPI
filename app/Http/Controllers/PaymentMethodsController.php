<?php

namespace App\Http\Controllers;

use App\DeliveryTransaction;
use App\PaymentMethods;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class PaymentMethodsController extends Controller
{
    public function createPaymentMethod(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.cardNumber' => 'required',
            'form.expDate' => 'required',
            'form.cvv' => 'required'
        ]);

        $form = $request->form;

        try {
            $nCard = new PaymentMethods();
            $nCard->idCliente = Auth::user()->idCliente;
            $nCard->token_card = $form['cardNumber'];
            $nCard->vencimiento = Crypt::encryptString($form['expDate']);
            $nCard->fechaRegistro = Carbon::now();
            $nCard->cvv = Crypt::encryptString($form['cvv']);
            $nCard->save();

            return response()->json([
                'error' => 0,
                'message' => 'Tarjeta agregada correctamente.'
            ], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Error al crear el método de pago.'
                ],
                500
            );
        }
    }

    public function getCustomerPaymentMethods()
    {
        try {
            $custPM = PaymentMethods::where('idCliente', Auth::user()->idCliente)->get();
            foreach ($custPM as $paymntMethd) {
                $paymntMethd->vencimiento = Crypt::decryptString($paymntMethd->vencimiento);
                $paymntMethd->cvv = Crypt::decryptString($paymntMethd->cvv);
            }

            return response()->json([
                'error' => 0,
                'data' => $custPM
            ], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Error al cargar los métodos de pago.'
                ],
                500
            );
        }
    }

    public function updatePaymentMethod(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.cardId' => 'required',
            'form.expDate' => 'required',
            'form.cvv' => 'required'
        ]);

        $form = $request->form;

        try {
            $currentCard = PaymentMethods::where('idFormaPago', $form['cardId']);
            $currentCard->update([
                'vencimiento' => Crypt::encryptString($form['expDate']),
                'cvv' => Crypt::encryptString($form['cvv'])
            ]);

            return response()->json([
                'error' => 0,
                'message' => 'Tarjeta actualizada correctamente.'
            ], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => 'Error al actualizar el método de pago.'
                ],
                500
            );
        }
    }

    public function saveFailTransaction(Request $request)
    {
        $request->validate([
            'payDetails' => 'required'
        ]);

        try {
            $newTransaction = new DeliveryTransaction();
            $newTransaction->idCliente = Auth::user()->idCliente;
            $newTransaction->reasonCode = $request->payDetails['reasonCode'];
            $newTransaction->reasonCodeDescription = $request->payDetails['reasonCodeDescription'];
            $newTransaction->authCode = $request->payDetails['authCode'];
            $newTransaction->orderNumber = $request->payDetails['orderNumber'];
            $newTransaction->fechaRegistro = Carbon::now();
            $newTransaction->save();

            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Transacción guardada correctamente.'
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
                    'message' => 'Error al guardar la transacción.'
                ],
                500
            );
        }

    }
}
