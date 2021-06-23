<?php

namespace App\Http\Controllers;

use App\BillingData;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingDataController extends Controller
{
    public function billingReport(Request $request){
        $request->validate(['form'=>'required']);
        try {
            $form = $request->form;
            $initDate = new Carbon(date('Y-m-d', strtotime($form['initDate'])) . ' 00:00:00');
            $finDate = new Carbon(date('Y-m-d', strtotime($form['finDate'])) . ' 23:59:59');
            $invoices = BillingData::with(['delivery.cliente'])
                ->whereBetween('fechaFacturacion',[$initDate,$finDate])->get();

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
