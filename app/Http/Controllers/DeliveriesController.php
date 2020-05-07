<?php

namespace App\Http\Controllers;

use App\Delivery;
use App\DetalleDelivery;
use App\Mail\ApplicationReceived;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use PDF;

class DeliveriesController extends Controller
{
    public function createDelivery(Request $request)
    {
        $request->validate(['deliveryForm' => 'required', 'orders' => 'required']);
        $hDelivery = $request->deliveryForm;
        $deliveryOrders = $request->orders;

        try {
            $nDelivery = new Delivery();
            $nDelivery->nomCliente          =   $hDelivery['nomCliente'];
            $nDelivery->numIdentificacion   =   $hDelivery['numIdentificacion'];
            $nDelivery->numCelular          =   $hDelivery['numCelular'];
            $nDelivery->fecha               =   $hDelivery['fecha'];
            $nDelivery->dirRecogida         =   $hDelivery['dirRecogida'];
            $nDelivery->email               =   $hDelivery['email'];
            $nDelivery->idCategoria         =   $hDelivery['idCategoria'];
            $nDelivery->save();

            foreach ($deliveryOrders as $detalle) {
                $nDetalle = new DetalleDelivery();
                $nDetalle->idDelivery       = $nDelivery['idDelivery'];
                $nDetalle->nFactura         = $detalle['nFactura'];
                $nDetalle->nomDestinatario  = $detalle['nomDestinatario'];
                $nDetalle->numCel           = $detalle['numCel'];
                $nDetalle->direccion        = $detalle['direccion'];
                $nDetalle->save();
            }

            $receivers = $hDelivery['email'];
            $orderDelivery = DetalleDelivery::where('idDelivery', $nDelivery['idDelivery'])->get();
            if ($this->sendmail($receivers, $nDelivery, $orderDelivery)) {
                return response()->json(
                    [
                        'error' => 0,
                        'message' => 'Solicitud de Delivery enviada correctamente. Recibirás un email con los detalles de tu reserva. Nos pondremos en contacto contigo.',
                        'nDelivery' => $nDelivery->idDelivery
                    ],
                    200
                );
            }


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
                    'message' => $ex->getMessage()
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
