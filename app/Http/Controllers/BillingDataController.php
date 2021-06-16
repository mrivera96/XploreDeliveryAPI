<?php

namespace App\Http\Controllers;

use App\BillingData;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingDataController extends Controller
{
    public function billingReport(){
        try {
            $invoices = BillingData::with(['delivery.cliente'])->get();

            return response()->json(
                [
                    'error' => 0,
                    'data' => $invoices
                ],
                200
            );
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
