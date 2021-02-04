<?php

namespace App\Http\Controllers;

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

    public function getCustomerPaymentMethods(){
        try {
            $custPM = PaymentMethods::where('idCliente',Auth::user()->idCliente)->get();
            foreach($custPM as $paymntMethd){
                $paymntMethd->vencimiento = Crypt::decryptString($paymntMethd->vencimiento);
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
}
