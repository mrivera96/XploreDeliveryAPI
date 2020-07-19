<?php

namespace App\Http\Controllers;

use App\RateCustomer;
use App\Tarifa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RatesController extends Controller
{
    public function getRates()
    {
        try {
            $tarifas = Tarifa::all();
            foreach ($tarifas as $tarifa) {
                $tarifa->category;
                $tarifa->precio = number_format($tarifa->precio, 2);
                $tarifa->cliente = $tarifa->customer;
            }
            return response()->json(
                [
                    'error' => 0,
                    'data' => $tarifas
                ],
                200
            );
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
        }
    }

    public function getCustomerRates()
    {
        try {
            $currCustomer = Auth::user()->idCliente;
            $custRates = RateCustomer::where('idCliente', $currCustomer)->get();
            $tarifas = [];

            if ($custRates->count() == 0) {
                $tarifas = Tarifa::where('idCliente', 1)->get();
            } else {
                foreach ($custRates as $value) {
                    $tarifa = Tarifa::where('idTarifaDelivery', $value->idTarifaDelivery)->get()->first();
                    array_push($tarifas, $tarifa);
                }
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => $tarifas
                ],
                200
            );
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
        }
    }

    public function updateRate(Request $request)
    {
        $idRate = $request->form["idTarifaDelivery"];
        $descRate = $request->form["descTarifa"];
        $idCategoria = $request->form["idCategoria"];
        $emin = $request->form["entregasMinimas"];
        $emax = $request->form["entregasMaximas"];
        $monto = $request->form["precio"];
        try {
            $currRate = Tarifa::where('idTarifaDelivery', $idRate);
            $currRate->update([
                    'idCategoria' => $idCategoria,
                    'descTarifa' => $descRate,
                    'entregasMinimas' => $emin,
                    'entregasMaximas' => $emax,
                    'precio' => $monto]
            );

            return response()->json([
                'error' => 0,
                'message' => 'Tarifa actualizada correctamente.'
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al actualizar la tarifa.'
            ], 500);
        }
    }

    public function createRate(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.descTarifa' => 'required',
            'form.idCategoria' => 'required',
            'form.entregasMinimas' => 'required',
            'form.entregasMaximas' => 'required',
            'form.precio' => 'required'
        ]);

        $descRate = $request->form["descTarifa"];
        $idCategoria = $request->form["idCategoria"];
        $emin = $request->form["entregasMinimas"];
        $emax = $request->form["entregasMaximas"];
        $monto = $request->form["precio"];


        try {
            $currRate = new Tarifa();
            $currRate->descTarifa = $descRate;
            $currRate->idCategoria = $idCategoria;
            $currRate->entregasMinimas = $emin;
            $currRate->entregasMaximas = $emax;
            $currRate->precio = $monto;
            $currRate->fechaRegistro = Carbon::now();
            if($request->form['idCliente'] == 1){
                $currRate->idCliente = 1;
            }
            $currRate->save();

            $lastIndex = Tarifa::query()->max('idTarifaDelivery');

            if(isset($request->customers)){
                $customers = $request->customers;
                if (sizeof($customers) > 0) {
                    for ($i = 0; $i < sizeof($customers); $i++) {
                        $existe = RateCustomer::where('idTarifaDelivery', $lastIndex)
                            ->where('idCliente', $customers[$i]['idCliente'])->count();

                        if ($existe == 0) {
                            $nCustRate = new RateCustomer();
                            $nCustRate->idTarifaDelivery = $lastIndex;
                            $nCustRate->idCliente = $customers[$i]['idCliente'];
                            $nCustRate->fechaRegistro = Carbon::now();
                            $nCustRate->save();

                        }
                    }

                }
            }

            return response()->json([
                'error' => 0,
                'message' => 'Tarifa agregada correctamente.'
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => $ex->getMessage() //'Error al agregar la tarifa.'
            ], 500);
        }
    }

    public function getCustomers(Request $request)
    {
        $request->validate([
            'idTarifa' => 'required'
        ]);
        $rateId = $request->idTarifa;
        try {
            $rateCustomers = RateCustomer::with('customer')->where('idTarifaDelivery', $rateId)->get();

            return response()->json([
                'error' => 0,
                'data' => $rateCustomers
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al cargar los clientes de la tarifa.'
            ], 500);
        }
    }

    public function removeCustomer(Request $request)
    {
        $request->validate([
            'idCliente' => 'required',
            'idTarifa' => 'required'
        ]);

        $customerId = $request->idCliente;
        $rateId = $request->idTarifa;
        try {
            RateCustomer::where('idTarifaDelivery', $rateId)
                ->where('idCliente', $customerId)->delete();

            return response()->json([
                'error' => 0,
                'message' => 'Cliente eliminado de la tarifa correctamente'
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Error al agregar la tarifa.'
            ], 500);
        }
    }

    public function addCustomer(Request $request)
    {
        $request->validate([
            'idCliente' => 'required',
            'idTarifa' => 'required'
        ]);

        $customerId = $request->idCliente;
        $rateId = $request->idTarifa;
        try {
            $existe = RateCustomer::where('idTarifaDelivery', $rateId)
                ->where('idCliente', $customerId)->count();

            if ($existe == 0) {
                $nCustRate = new RateCustomer();
                $nCustRate->idTarifaDelivery = $rateId;
                $nCustRate->idCliente = $customerId;
                $nCustRate->fechaRegistro = Carbon::now();
                $nCustRate->save();

                return response()->json([
                    'error' => 0,
                    'message' => 'La tarifa ha sido asignada correctamente'
                ], 200);

            } else {
                return response()->json([
                    'error' => 1,
                    'message' => 'El cliente ya tiene asignada esta tarifa'
                ], 500);
            }


        } catch (\Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json([
                'error' => 1,
                'message' => 'Ha ocurrido un error al agregar el cliente'
            ], 500);
        }
    }
}
