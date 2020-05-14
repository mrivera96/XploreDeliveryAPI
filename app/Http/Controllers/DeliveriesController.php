<?php

namespace App\Http\Controllers;

use App\ContratoDelivery;
use App\Delivery;
use App\DetalleDelivery;
use App\Mail\ApplicationReceived;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use PDF;
use Carbon\Carbon;
use App\Tarifa;
use Illuminate\Support\Facades\Auth;

class DeliveriesController extends Controller
{
    public function createDelivery(Request $request)
    {
        $request->validate(['deliveryForm' => 'required', 'orders' => 'required', 'pago' => 'required']);
        $hDelivery = $request->deliveryForm;
        $deliveryOrders = $request->orders;
        $pago = $request->pago;

        try {
            $nDelivery = new Delivery();
            $nDelivery->nomCliente          =   $hDelivery['nomCliente'];
            $nDelivery->numIdentificacion   =   $hDelivery['numIdentificacion'];
            $nDelivery->numCelular          =   $hDelivery['numCelular'];
            $date = date('Y-m-d', strtotime($hDelivery['fecha']));
            $time = $hDelivery['hora'];
            $datetime = $date . ' ' . $time;
            $nDelivery->fechaReserva               =   new Carbon($datetime);
            $nDelivery->dirRecogida         =   $hDelivery['dirRecogida'];
            $nDelivery->email               =   $hDelivery['email'];
            $nDelivery->idCategoria         =   $hDelivery['idCategoria'];
            $nDelivery->idEstado            =   32;
            $nDelivery->tarifaBase          =   $pago['baseRate'];
            $nDelivery->recargos            =   $pago['recargos'];
            $nDelivery->total            =   $pago['total'];
            $nDelivery->save();

            foreach ($deliveryOrders as $detalle) {
                $nDetalle = new DetalleDelivery();
                $nDetalle->idDelivery       = $nDelivery['idDelivery'];
                $nDetalle->nFactura         = $detalle['nFactura'];
                $nDetalle->nomDestinatario  = $detalle['nomDestinatario'];
                $nDetalle->numCel           = $detalle['numCel'];
                $nDetalle->direccion        = $detalle['direccion'];
                $nDetalle->distancia        = $detalle['distancia'];
                $nDetalle->save();
            }

            $receivers = $hDelivery['email'];
            $orderDelivery = DetalleDelivery::where('idDelivery', $nDelivery['idDelivery'])->get();
            $this->sendmail($receivers, $nDelivery, $orderDelivery);

            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Solicitud de Delivery enviada correctamente. Recibirás un email con los detalles de tu reserva. Nos pondremos en contacto contigo.',
                    'nDelivery' => $nDelivery->idDelivery
                ],
                200
            );

            /*return response()->json(
                [
                    'error' => 0,
                    'message' => 'Solicitud de Delivery enviada correctamente. Recibirás un email con los detalles de tu reserva. Nos pondremos en contacto contigo.',
                    'nDelivery'=>$nDelivery->idDelivery
                ],
                200
            );*/
        } catch (Exception $ex) {
            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage(),
                    'stackTrace' => $ex->getTrace()
                ],
                500
            );
        }
    }

    public function sendmail($mail, $delivery, $orders)
    {
        $data["email"] = $mail;
        $data["client_name"] = $delivery->nomCliente;
        $data["subject"] = 'Solicitud de servicio Xplore Delivery';
        $data["delivery"] = $delivery;
        $data["orderDelivery"] = $orders;

        $pdf = PDF::loadView('applicationSheet', $data);

        try {
            Mail::send('mails.view', $data, function ($message) use ($data, $pdf) {
                $message->to($data["email"], $data["client_name"])
                    ->subject($data["subject"])
                    ->attachData($pdf->output(), "Solicitud_XploreDelivery.pdf");
            });
        } catch (Exception $exception) {
            $this->serverstatuscode = "0";
            $this->serverstatusdes = $exception->getMessage();
        }
        if (Mail::failures()) {
            return false;
        } else {
            return true;
        }
    }

    public function getTarifas()
    {
        try {
            $tarifas = Tarifa::all();
            foreach ($tarifas as $tarifa) {
                $tarifa->category;
            }
            return response()->json(
                [
                    'error' => 0,
                    'data' => $tarifas
                ],
                200
            );
        } catch (Exception $ex) {
            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
        }
    }


    public function list(){
        try {
            $deliveries = Delivery::all();
            foreach ($deliveries as $delivery) {
                $delivery->category;
                $delivery->detalle;
                $delivery->estado;
            }
            return response()->json(
                [
                    'error' => 0,
                    'data' => $deliveries
                ],
                200
            );
        } catch (Exception $ex) {
            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
        }
    }

    public function getById(Request $request){
        try {
            $delivery = Delivery::where('idDelivery', $request->id)->get()->first();

                $delivery->category;
                $delivery->detalle;
                $delivery->estado;

            return response()->json(
                [
                    'error' => 0,
                    'data' => $delivery
                ],
                200
            );
        } catch (Exception $ex) {
            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
        }
    }


    public function generateContract(Request $request){
        $request->validate(['idDelivery' => 'required', 'vehiculoAsignado' => 'required']);
        $idDelivery = $request->idDelivery;
        $vehiculo= $request->motivoAnul;

        try {
            $tarifa = Tarifa::all();
            $newContract = new ContratoDelivery();
            $currDelivery = Delivery::where('idDelivery', $idDelivery)->get();
            $ordersCount = $currDelivery->detalle()->count();
            $newContract->idDelivery = $idDelivery;
            /*$newContract->idTarifaDelivery = ;
            $newContract->idDelivery = ;
            $newContract->idDelivery = ;
            $newContract->idDelivery = ;
            $newContract->idDelivery = ;




            $currDelivery->idEstado = 48;
            $currDelivery->numContrato = $motivoAnul;
            $currDelivery->save();*/

            /*$receivers = $hDelivery['email'];
            $orderDelivery = DetalleDelivery::where('idDelivery', $nDelivery['idDelivery'])->get();
            $this->sendmail($receivers, $nDelivery, $orderDelivery);
*/
            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Delivery anulada correctamente.',

                ],
                200
            );

        } catch (Exception $ex) {
            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
        }

    }

    public function cancelDelivery(Request $request){
        $request->validate(['idDelivery' => 'required', 'motivoAnul' => 'required']);
        $idDelivery = $request->idDelivery;
        $motivoAnul= $request->motivoAnul;

        try {
            $currDelivery = Delivery::where('idDelivery', $idDelivery)->get();
            $currDelivery->idEstado = 38;
            $currDelivery->usrAnuloReserva = Auth::user()->idUsuario;
            $currDelivery->fechaAnulado = Carbon::now();
            $currDelivery->motivoAnul = $motivoAnul;
            $currDelivery->save();

            /*$receivers = $hDelivery['email'];
            $orderDelivery = DetalleDelivery::where('idDelivery', $nDelivery['idDelivery'])->get();
            $this->sendmail($receivers, $nDelivery, $orderDelivery);
*/
            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Delivery anulada correctamente.',

                ],
                200
            );

        } catch (Exception $ex) {
            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
        }
    }



    /* public function test()
    {
        $delivery = Delivery::where('idDelivery', 14)->get()->first();
        $orderDelivery = DetalleDelivery::where('idDelivery', $delivery->idDelivery)->get();

        $data["email"] = 'jylrivera96@gmail.com';
        $data["client_name"] = $delivery->nomCliente;
        $data["subject"] = 'Solicitud de servicio Xplore Delivery';
        $data["delivery"] = $delivery;
        $data["orderDelivery"] = $orderDelivery;


        $pdf = PDF::loadView('applicationSheet', $data);

        try {
            Mail::send('mails.view', $data, function ($message) use ($data, $pdf) {
                $message->to($data["email"], $data["client_name"])
                    ->subject($data["subject"])
                    ->attachData($pdf->output(), "Solicitud_XploreDelivery.pdf");
            });
            return response('OK');
        } catch (Exception $exception) {
            dd($exception->getMessage());

        }


    }*/
}
