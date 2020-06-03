<?php

namespace App\Http\Controllers;

use App\ContratoDelivery;
use App\CtrlEstadoDelivery;
use App\Delivery;
use App\DeliveryClient;
use App\DetalleDelivery;
use App\Estado;
use App\Mail\ApplicationReceived;
use App\User;
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
            $nDelivery->nomCliente = $hDelivery['nomCliente'];
            $nDelivery->numIdentificacion = $hDelivery['numIdentificacion'];
            $nDelivery->numCelular = $hDelivery['numCelular'];
            $date = date('Y-m-d', strtotime($hDelivery['fecha']));
            $time = $hDelivery['hora'];
            $datetime = $date . ' ' . $time;
            $nDelivery->fechaReserva = new Carbon($datetime);
            $nDelivery->dirRecogida = $hDelivery['dirRecogida'];
            $nDelivery->email = $hDelivery['email'];
            $nDelivery->idCategoria = $hDelivery['idCategoria'];
            $nDelivery->idEstado = 34;
            $nDelivery->tarifaBase = $pago['baseRate'];
            $nDelivery->recargos = $pago['recargos'];
            $nDelivery->total = $pago['total'];
            $nDelivery->save();

            $lastId = Delivery::query()->max('idDelivery');

            foreach ($deliveryOrders as $detalle) {
                $nDetalle = new DetalleDelivery();
                $nDetalle->idDelivery = $lastId;
                $nDetalle->nFactura = $detalle['nFactura'];
                $nDetalle->nomDestinatario = $detalle['nomDestinatario'];
                $nDetalle->numCel = $detalle['numCel'];
                $nDetalle->direccion = $detalle['direccion'];
                $nDetalle->distancia = $detalle['distancia'];
                $nDetalle->tarifaBase = $detalle['tarifaBase'];
                $nDetalle->recargo = $detalle['recargo'];
                $nDetalle->cTotal = $detalle['cTotal'];
                $nDetalle->save();
            }

            $receivers = $hDelivery['email'];
            $orderDelivery = DetalleDelivery::where('idDelivery', $lastId)->get();
            $this->sendmail($receivers, $nDelivery, $orderDelivery);

            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Solicitud de Delivery enviada correctamente. Recibirás un email con los detalles de tu reserva. Nos pondremos en contacto contigo.',
                    'nDelivery' => $lastId
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

    public function createCustomerDelivery(Request $request)
    {
        $request->validate(['deliveryForm' => 'required', 'orders' => 'required', 'pago' => 'required']);
        $hDelivery = $request->deliveryForm;
        $deliveryOrders = $request->orders;
        $pago = $request->pago;


        try {
            $customerDetails = DeliveryClient::where('idCliente', Auth::user()->idCliente)->get()->first();


            $nDelivery = new Delivery();
            $nDelivery->nomCliente = $customerDetails->nomEmpresa;
            $nDelivery->numIdentificacion = $customerDetails->numIdentificacion;
            $nDelivery->numCelular = $customerDetails->numTelefono;
            $date = date('Y-m-d', strtotime($hDelivery['fecha']));
            $time = $hDelivery['hora'];
            $datetime = $date . ' ' . $time;
            $nDelivery->fechaReserva = new Carbon($datetime);
            $nDelivery->dirRecogida = $hDelivery['dirRecogida'];
            $nDelivery->email = $customerDetails->email;
            $nDelivery->idCategoria = $hDelivery['idCategoria'];
            $nDelivery->idEstado = 34;
            $nDelivery->tarifaBase = $pago['baseRate'];
            $nDelivery->recargos = $pago['recargos'];
            $nDelivery->total = $pago['total'];
            $nDelivery->idCliente = Auth::user()->idCliente;
            $nDelivery->save();


            $lastId = Delivery::query()->max('idDelivery');

            foreach ($deliveryOrders as $detalle) {
                $nDetalle = new DetalleDelivery();
                $nDetalle->idDelivery = $lastId;
                $nDetalle->nFactura = $detalle['nFactura'];
                $nDetalle->nomDestinatario = $detalle['nomDestinatario'];
                $nDetalle->numCel = $detalle['numCel'];
                $nDetalle->direccion = $detalle['direccion'];
                $nDetalle->distancia = $detalle['distancia'];
                $nDetalle->tarifaBase = $detalle['tarifaBase'];
                $nDetalle->recargo = $detalle['recargo'];
                $nDetalle->cTotal = $detalle['cTotal'];

                $nDetalle->save();
            }

            $receivers = $customerDetails->email;
            $orderDelivery = DetalleDelivery::where('idDelivery', $lastId)->get();
            $this->sendmail($receivers, $nDelivery, $orderDelivery);

            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Solicitud de Delivery enviada correctamente. Recibirás un email con los detalles de tu reserva. Nos pondremos en contacto contigo.',
                    'nDelivery' => $lastId
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
        $data["subject"] = 'Xplore Delivery No. '.$delivery->idDelivery;
        $data["delivery"] = $delivery;
        $data["orderDelivery"] = $orders;
        $data["from"] = 'Xplore Delivery';

        $pdf = PDF::loadView('applicationSheet', $data);

        try {
            Mail::send('mails.view', $data, function ($message) use ($data, $pdf) {
                $message
                    ->from('melvin.rivera@xplorerentacar.com', $data["from"])
                    ->to($data["email"], $data["client_name"])
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


    public function list()
    {
        try {
            $deliveriesDia = Delivery::whereDate('fechaReserva', Carbon::today())->get();
            $deliveriesTomorrow = Delivery::whereDate('fechaReserva', Carbon::tomorrow())->get();
            $allDeliveries = Delivery::all();

            foreach ($deliveriesDia as $delivery) {
                $delivery->category;
                $delivery->detalle;
                $delivery->estado;
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d h:i a');
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->total = number_format($delivery->total, 2);
                $delivery->entregas = count($delivery->detalle);
            }

            foreach ($deliveriesTomorrow as $delivery) {
                $delivery->category;
                $delivery->detalle;
                $delivery->estado;
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d h:i a');
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->total = number_format($delivery->total, 2);
                $delivery->entregas = count($delivery->detalle);
            }

            foreach ($allDeliveries as $delivery) {
                $delivery->category;
                $delivery->detalle;
                $delivery->estado;
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d h:i a');
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->total = number_format($delivery->total, 2);
                $delivery->entregas = count($delivery->detalle);
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => array('deliveriesDia' => $deliveriesDia, 'todas' => $allDeliveries,
                        'deliveriesManiana' => $deliveriesTomorrow)
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

    public function getById(Request $request)
    {
        try {
            if (Auth::user()->idPerfil == 1) {
                $delivery = Delivery::where('idDelivery', $request->id)->get()->first();
            } else {
                $delivery = Delivery::where('idCliente', Auth::user()->idCliente)->where('idDelivery', $request->id)->get()->first();
            }

            $delivery->category;
            $delivery->detalle;
            $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('j/m/Y, h:i a');
            $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
            $delivery->recargos = number_format($delivery->recargos, 2);
            $delivery->total = number_format($delivery->total, 2);
            foreach ($delivery->detalle as $detail) {
                $detail->conductor = $detail->conductor;
                $detail->tarifaBase = number_format($detail->tarifaBase, 2);
                $detail->recargo = number_format($detail->recargo, 2);
                $detail->cTotal = number_format($detail->cTotal, 2);
            }
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


    public function generateContract(Request $request)
    {
        $request->validate(['idDelivery' => 'required', 'vehiculoAsignado' => 'required']);
        $idDelivery = $request->idDelivery;
        $vehiculo = $request->motivoAnul;

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

    public function cancelDelivery(Request $request)
    {
        $request->validate(['idDelivery' => 'required', 'motivoAnul' => 'required']);
        $idDelivery = $request->idDelivery;
        $motivoAnul = $request->motivoAnul;

        try {
            $currDelivery = Delivery::where('idDelivery', $idDelivery)->get();
            $currDelivery->idEstado = 36;
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

    public function updateDeliveried(Request $request)
    {
        $request->validate([
            'idDetalle' => 'required',
            'idConductor' => ' required',
            'nomRecibio' => 'required',
            'fechaEntrega' => 'required']);
        $idDetalle = $request->idDetalle;
        $idConductor = $request->idConductor;
        $nomRecibio = $request->nomRecibio;
        $fechaEntrega = new Carbon($request->fechaEntrega);
        try {
            $detail = DetalleDelivery::where('idDetalle', $idDetalle);
            $detail->update(['idConductor' => $idConductor,
                'nomRecibio' => $nomRecibio,
                'fechaEntrega' => $fechaEntrega,
                'entregado' => true]);

            return response()->json([
                'codError' => 0,
                'messageError' => null,
                'message' => 'la entrega se registró correctamente'
            ],
                200);
        } catch (Exception $ex) {
            return response()->json(['codError' => 1, 'messageError' => $ex->getMessage()], 500);
        }

    }

    public function testContractFormat(Request $request)
    {
        $delivery = Delivery::where('idDelivery', $request->idDelivery)->get()->first();
        $orderDelivery = DetalleDelivery::where('idDelivery', $delivery->idDelivery)->get();

        return view('deliveryContract', compact('delivery', 'orderDelivery'));

    }

    public function testReserveFormat(Request $request)
    {
        $delivery = Delivery::where('idDelivery', $request->idDelivery)->get()->first();
        $orderDelivery = DetalleDelivery::where('idDelivery', $delivery->idDelivery)->get();

        return view('applicationSheet', compact('delivery', 'orderDelivery'));

    }

    public function assignDelivery(Request $request)
    {
        $idConductor = $request->assignForm['idConductor'];
        //$idVehiculo = $request->asignForm['idVehiculo'];
        $idDelivery = $request->idDelivery;
        try {
            $delivery = Delivery::where('idDelivery', $idDelivery);
            $delivery->update(['idEstado' => 37]);

            $details = DetalleDelivery::where('idDelivery', $idDelivery);
            $details->update(['idEstado' => 37, 'idConductor' => $idConductor]);
            $conductor = User::where('idUsuario', $idConductor)->get()->first();

            $nCtrl = new CtrlEstadoDelivery();
            $nCtrl->idDelivery = $idDelivery;
            $nCtrl->idEstado = 37;
            $nCtrl->idUsuario = Auth::user()->idUsuario;
            $nCtrl->fechaRegistro = Carbon::now();
            $nCtrl->save();


            return response()->json([
                'error' => 0,
                'data' => 'Reserva asignada correctamente a: ' . $conductor->nomUsuario],
                200);

        } catch (Exception $ex) {
            return response()->json([
                'error' => 1,
                'message' => $ex->getMessage()],
                500);
        }
    }

    public function changeStateDelivery(Request $request)
    {
        $idEstado = $request->idEstado['idEstado'];
        $idDelivery = $request->idDelivery;
        try {
            $delivery = Delivery::where('idDelivery', $idDelivery);
            if ($idEstado == 37) {
                $idConductor = $request->idEstado['idConductor'];
                $delivery->update(['idEstado' => $idEstado, 'idConductor' => $idConductor]);
            }
            $delivery->update(['idEstado' => $idEstado]);

            $details = DetalleDelivery::where('idDelivery', $idDelivery);
            $details->update(['idEstado' => $idEstado]);
            $estado = Estado::where('idEstado', $idEstado)->get()->first();

            $nCtrl = new CtrlEstadoDelivery();
            $nCtrl->idDelivery = $idDelivery;
            $nCtrl->idEstado = $idEstado;
            $nCtrl->idUsuario = Auth::user()->idUsuario;
            $nCtrl->fechaRegistro = Carbon::now();
            $nCtrl->save();


            return response()->json([
                'error' => 0,
                'data' => 'Se cambió el estado de reserva a: ' . $estado->descEstado],
                200);

        } catch (Exception $ex) {
            return response()->json([
                'error' => 1,
                'message' => $ex->getMessage()],
                500);
        }
    }

    public function finishDelivery(Request $request)
    {

        $idDelivery = $request->idDelivery;
        try {
            $delivery = Delivery::where('idDelivery', $idDelivery);
            $delivery->update(['idEstado' => 39]);

            $details = DetalleDelivery::where('idDelivery', $idDelivery);
            $details->update(['idEstado' => 39]);

            $nCtrl = new CtrlEstadoDelivery();
            $nCtrl->idDelivery = $idDelivery;
            $nCtrl->idEstado = 39;
            $nCtrl->idUsuario = Auth::user()->idUsuario;
            $nCtrl->fechaRegistro = Carbon::now();
            $nCtrl->save();

            return response()->json([
                'error' => 0,
                'data' => 'Reserva finalizada correctamente.'],
                200);

        } catch (Exception $ex) {
            return response()->json([
                'error' => 1,
                'message' => $ex->getMessage()],
                500);
        }
    }


    public function getCustomerDeliveries()
    {

        try {
            $user = Auth::user();
            $deliveriesDia = Delivery::where('idCliente', $user->idCliente)->whereDate('fechaReserva', Carbon::today())->get();
            $deliveriesTomorrow = Delivery::where('idCliente', $user->idCliente)->whereDate('fechaReserva', Carbon::tomorrow())->get();
            $allDeliveries = Delivery::where('idCliente', $user->idCliente)->get();

            foreach ($deliveriesDia as $delivery) {
                $delivery->category;
                $delivery->detalle;
                $delivery->estado;
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d h:i a');
                //$delivery->fechaReserva = date('d-m-Y h:i', strtotime($delivery->fechaReserva));
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->total = number_format($delivery->total, 2);
                $delivery->entregas = count($delivery->detalle);
            }

            foreach ($deliveriesTomorrow as $delivery) {
                $delivery->category;
                $delivery->detalle;
                $delivery->estado;
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d h:i a');
                //$delivery->fechaReserva = date('d-m-Y h:i', strtotime($delivery->fechaReserva));
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->total = number_format($delivery->total, 2);
                $delivery->entregas = count($delivery->detalle);
            }

            foreach ($allDeliveries as $delivery) {
                $delivery->category;
                $delivery->detalle;
                $delivery->estado;
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d h:i a');
                //$delivery->fechaReserva = date('d-m-Y h:i', strtotime($delivery->fechaReserva));
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->total = number_format($delivery->total, 2);
                $delivery->entregas = count($delivery->detalle);
            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => array('deliveriesDia' => $deliveriesDia, 'todas' => $allDeliveries,
                        'deliveriesManiana' => $deliveriesTomorrow)
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

    public function getCustomerOders()
    {
        try {
            $user = Auth::user();
            $deliveriesDia = Delivery::where('idCliente', $user->idCliente)->whereDate('fechaReserva', Carbon::today())->get();
            $allDeliveries = Delivery::where('idCliente', $user->idCliente)->get();
            $pedidosDia = [];
            $todosPedidos = [];

            foreach ($deliveriesDia as $delivery) {
                $detail = DetalleDelivery::where('idDelivery', $delivery->idDelivery)->get();
                foreach ($detail as $dtl) {
                    $dtl->conductor;
                    $dtl->estado;
                    $dtl->tarifaBase = number_format($dtl->tarifaBase, 2);
                    $dtl->recargo = number_format($dtl->recargo, 2);
                    $dtl->cTotal = number_format($dtl->cTotal, 2);
                    array_push($pedidosDia, $dtl);
                }

            }

            foreach ($allDeliveries as $delivery) {
                $detail = DetalleDelivery::where('idDelivery', $delivery->idDelivery)->get();
                foreach ($detail as $dtl) {
                    $dtl->estado;
                    $dtl->conductor;
                    $dtl->tarifaBase = number_format($dtl->tarifaBase, 2);
                    $dtl->recargo = number_format($dtl->recargo, 2);
                    $dtl->cTotal = number_format($dtl->cTotal, 2);
                    array_push($todosPedidos, $dtl);
                }

            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => array('pedidosDia' => $pedidosDia, 'todos' => $todosPedidos)
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

    public function getOrders()
    {
        try {
            $user = Auth::user();
            $deliveriesDia = Delivery::whereDate('fechaReserva', Carbon::today())->get();
            $allDeliveries = Delivery::all();
            $pedidosDia = [];
            $todosPedidos = [];

            foreach ($deliveriesDia as $delivery) {
                $detail = DetalleDelivery::where('idDelivery', $delivery->idDelivery)->get();
                foreach ($detail as $dtl) {
                    $dtl->estado;
                    $dtl->conductor;
                    $dtl->tarifaBase = number_format($dtl->tarifaBase, 2);
                    $dtl->recargo = number_format($dtl->recargo, 2);
                    $dtl->cTotal = number_format($dtl->cTotal, 2);
                    array_push($pedidosDia, $dtl);
                }

            }

            foreach ($allDeliveries as $delivery) {
                $detail = DetalleDelivery::where('idDelivery', $delivery->idDelivery)->get();
                foreach ($detail as $dtl) {
                    $dtl->estado;
                    $dtl->conductor;
                    $dtl->tarifaBase = number_format($dtl->tarifaBase, 2);
                    $dtl->recargo = number_format($dtl->recargo, 2);
                    $dtl->cTotal = number_format($dtl->cTotal, 2);
                    array_push($todosPedidos, $dtl);
                }

            }

            return response()->json(
                [
                    'error' => 0,
                    'data' => array('pedidosDia' => $pedidosDia, 'todos' => $todosPedidos)
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
