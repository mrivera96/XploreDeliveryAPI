<?php

namespace App\Http\Controllers;

use App\PaymentMethods;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentMethodsController extends Controller
{
    public function createPaymentMethod(Request $request)
    {
        $request->validate([
            'cardNumber',
            'expDate',
            'cvv'
        ]);

        $expYear = intval(substr($request->expDate, 0, 2));
        $expMonth = intval(substr($request->expDate, 3, 2));

        try {
            $nCard = new PaymentMethods();
            $nCard->token_card = $request->cardNumber;
            $nCard->mes = $expMonth;
            $nCard->anio = $expYear;
            $nCard->fechaRegistro = Carbon::now();
            $nCard->cvv = $request->cvv;
            $nCard->save();

            return response()->json([
                'error' => 0,
                'message' => 'Categoría agregada correctamente.'
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
