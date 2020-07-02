<?php

namespace App\Http\Controllers;

use App\Category;
use App\ContratoDelivery;
use App\CtrlEstadoDelivery;
use App\Delivery;
use App\DeliveryClient;
use App\DetalleDelivery;
use App\Estado;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Tarifa;
use Illuminate\Support\Facades\Auth;

class DeliveriesController extends Controller
{
    /****
     * CREATE DELIVERIES FUNCTIONS
     ****/

    public function createDelivery(Request $request)
    {
        $request->validate([
            'deliveryForm' => 'required',
            'deliveryForm.nomCliente' => 'required',
            'deliveryForm.numIdentificacion' => 'required',
            'deliveryForm.numCelular' => 'required',
            'deliveryForm.fecha' => 'required',
            'deliveryForm.hora' => 'required',
            'deliveryForm.dirRecogida' => 'required',
            'deliveryForm.email' => 'required',
            'deliveryForm.idCategoria' => 'required',
            'orders' => 'required',
            'pago' => 'required'
        ]);
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
            $nDelivery->instrucciones = $hDelivery['instrucciones'];
            $nDelivery->fechaRegistro = Carbon::now();
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
                $nDetalle->instrucciones = $detalle['instrucciones'];
                $nDetalle->save();
            }

            $receivers = $hDelivery['email'];
            $this->sendmail($receivers, $lastId);

            return response()->json([
                'error' => 0,
                'message' => 'Solicitud de Delivery enviada correctamente.
                    Recibirás un email con los detalles de tu reserva. Nos pondremos en contacto contigo.',
                'nDelivery' => $lastId],
                200
            );

        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array('context' => $ex->getTrace()));
            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
        }
    }

    public function createCustomerDelivery(Request $request)
    {
        $request->validate([
            'deliveryForm' => 'required',
            'deliveryForm.fecha' => 'required',
            'deliveryForm.hora' => 'required',
            'deliveryForm.dirRecogida' => 'required',
            'deliveryForm.idCategoria' => 'required',
            'orders' => 'required',
            'pago' => 'required'
        ]);

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
            $time = date('H:i', strtotime($hDelivery['hora']));
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
            $nDelivery->instrucciones = $hDelivery['instrucciones'];
            $nDelivery->fechaRegistro = Carbon::now();
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
                $nDetalle->instrucciones = $detalle['instrucciones'];

                $nDetalle->save();
            }

            $receivers = $customerDetails->email;
            $this->sendmail($receivers, $lastId);

            return response()->json([
                'error' => 0,
                'message' => 'Solicitud de Delivery enviada correctamente.
                    Recibirás un email con los detalles de tu reserva. Nos pondremos en contacto contigo.',
                'nDelivery' => $lastId],
                200
            );

        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array('User' => Auth::user()->cliente()->nomEmpresa,
                'context' => $ex->getTrace()));
            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getMessage()
                ],
                500
            );
        }

    }

    /****
     * CHANGE DELIVERY HOUR
     ****/
    public function changeDeliveryHour(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.hora' => 'required',
            'form.idDelivery' => 'required'
        ]);

        $rDelivery = $request->form;
        $currDelivery = Delivery::find($rDelivery['idDelivery']);

        try {
            $date = date('Y-m-d', strtotime($currDelivery->fechaReserva));
            $time = date('H:i', strtotime($rDelivery['hora']));
            $datetime = $date . ' ' . $time;
            $currDelivery->update([
                'fechaReserva' => new Carbon($datetime)
            ]);

            $this->sendChangeNotification($currDelivery->email, $currDelivery->idDelivery);

            return response()->json([
                'error' => 0,
                'message' => 'Hora de recogida actualizada correctamente'],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), array('User' => Auth::user()->nomUsuario, 'context' => $ex->getTrace()));
            return response()->json(
                [
                    'error' => 1,
                    'message' => $ex->getTrace()
                ],
                500
            );
        }

    }

    /****
     * GET DELIVERIES FUNCTIONS
     ****/
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
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d H:i');
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->total = number_format($delivery->total, 2);
                $delivery->entregas = count($delivery->detalle);
            }

            foreach ($deliveriesTomorrow as $delivery) {
                $delivery->category;
                $delivery->detalle;
                $delivery->estado;
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d H:i');
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->total = number_format($delivery->total, 2);
                $delivery->entregas = count($delivery->detalle);
            }

            foreach ($allDeliveries as $delivery) {
                $delivery->category;
                $delivery->detalle;
                $delivery->estado;
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d H:i');
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->total = number_format($delivery->total, 2);
                $delivery->entregas = count($delivery->detalle);
            }

            return response()->json([
                'error' => 0,
                'data' => array('deliveriesDia' => $deliveriesDia, 'todas' => $allDeliveries,
                    'deliveriesManiana' => $deliveriesTomorrow)],
                200
            );
        } catch (Exception $ex) {
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
            $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('d/m/Y, h:i a');
            $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
            $delivery->recargos = number_format($delivery->recargos, 2);
            $delivery->total = number_format($delivery->total, 2);
            foreach ($delivery->detalle as $detail) {
                $detail->conductor;
                $detail->estado;
                $detail->tarifaBase = number_format($detail->tarifaBase, 2);
                $detail->recargo = number_format($detail->recargo, 2);
                $detail->cTotal = number_format($detail->cTotal, 2);
            }
            $delivery->estado;

            return response()->json([
                'error' => 0,
                'data' => $delivery],
                200
            );
        } catch (Exception $ex) {
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

    public function getPendingDeliveries()
    {
        try {
            $pendingDeliveries = DB::select('EXEC [Delivery].[ListadoEntregasPorAsignar]');
            foreach ($pendingDeliveries as $delivery) {
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d H:i');
            }
            return response()->json([
                'error' => 0,
                'data' => $pendingDeliveries],
                500
            );
        } catch (Exception $ex) {
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

    public function getCustomerDeliveries()
    {
        try {
            $user = Auth::user();
            $deliveriesDia = Delivery::where('idCliente', $user->idCliente)
                ->whereDate('fechaReserva', Carbon::today())->get();
            $deliveriesTomorrow = Delivery::where('idCliente', $user->idCliente)
                ->whereDate('fechaReserva', Carbon::tomorrow())->get();
            $allDeliveries = Delivery::where('idCliente', $user->idCliente)->get();
            $finishedDeliveries = Delivery::where('idCliente', $user->idCliente)->where('idEstado', 39)->get();

            foreach ($deliveriesDia as $delivery) {
                $delivery->category;
                $delivery->detalle;
                $delivery->estado;
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d H:i');
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
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d H:i');
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
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d H:i');
                //$delivery->fechaReserva = date('d-m-Y h:i', strtotime($delivery->fechaReserva));
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->total = number_format($delivery->total, 2);
                $delivery->entregas = count($delivery->detalle);
            }

            foreach ($finishedDeliveries as $delivery) {
                $delivery->category;
                $delivery->detalle;
                $delivery->estado;
                $delivery->fechaReserva = \Carbon\Carbon::parse($delivery->fechaReserva)->format('Y-m-d H:i');
                $delivery->tarifaBase = number_format($delivery->tarifaBase, 2);
                $delivery->recargos = number_format($delivery->recargos, 2);
                $delivery->total = number_format($delivery->total, 2);
                $delivery->entregas = count($delivery->detalle);
            }

            return response()->json([
                'error' => 0,
                'data' => array(
                    'deliveriesDia' => $deliveriesDia,
                    'todas' => $allDeliveries,
                    'deliveriesManiana' => $deliveriesTomorrow,
                    'deliveriesFinalizadas' => $finishedDeliveries)],
                200
            );
        } catch (Exception $ex) {
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

    public function getCustomerOders()
    {
        try {
            $user = Auth::user();
            $deliveriesDia = Delivery::where('idCliente', $user->idCliente)
                ->whereDate('fechaReserva', Carbon::today())->get();
            $allDeliveries = Delivery::where('idCliente', $user->idCliente)->get();
            $pedidosDia = [];
            $todosPedidos = [];

            foreach ($deliveriesDia as $delivery) {
                $detail = DetalleDelivery::where('idDelivery', $delivery->idDelivery)->get();
                foreach ($detail as $dtl) {
                    $dtl->conductor;
                    $dtl->estado;
                    $dtl->fechaEntrega = \Carbon\Carbon::parse($dtl->fechaEntrega)->format('Y-m-d H:i');
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
                    $dtl->fechaEntrega = \Carbon\Carbon::parse($dtl->fechaEntrega)->format('Y-m-d H:i');
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

    public function getOrders()
    {
        try {
            $deliveriesDia = Delivery::whereDate('fechaReserva', Carbon::today())->get();
            $allDeliveries = Delivery::all();
            $pedidosDia = [];
            $todosPedidos = [];

            foreach ($deliveriesDia as $delivery) {
                $detail = DetalleDelivery::where('idDelivery', $delivery->idDelivery)->get();
                foreach ($detail as $dtl) {
                    $dtl->estado;
                    $dtl->delivery;
                    $dtl->fechaEntrega = \Carbon\Carbon::parse($dtl->fechaEntrega)->format('Y-m-d H:i');
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
                    $dtl->delivery;
                    $dtl->fechaEntrega = \Carbon\Carbon::parse($dtl->fechaEntrega)->format('Y-m-d H:i');
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

    /*
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
                $newContract->idTarifaDelivery = ;
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

    return response()->json(
        [
            'error' => 0,
            'message' => 'Delivery anulada correctamente.',

        ],
        200
    );

} catch (Exception $ex) {
    Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
    return response()->json(
        [
            'error' => 1,
            'message' => $ex->getMessage()
        ],
        500
    );
}

}*/

    /****
     * UPDATE DELIVERIES FUNCTIONS
     ****/
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


            return response()->json(
                [
                    'error' => 0,
                    'data' => 'Reserva asignada correctamente a: ' . $conductor->nomUsuario
                ],
                200
            );

        } catch (Exception $ex) {
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

    public function changeStateDelivery(Request $request)
    {
        $idEstado = $request->idEstado['idEstado'];
        $idDelivery = $request->idDelivery;
        try {
            $delivery = Delivery::where('idDelivery', $idDelivery);

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


            return response()->json(
                [
                    'error' => 0,
                    'data' => 'Se cambió el estado de reserva a: ' . $estado->descEstado
                ],
                200
            );

        } catch (Exception $ex) {
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

    public function changeOrderState(Request $request)
    {
        $request->validate([
            'idEstado' => 'required',
            'idDetalle' => 'required'
        ]);

        $stateId = $request->idEstado;
        $orderId = $request->idDetalle;
        try {


            $details = DetalleDelivery::where('idDetalle', $orderId);
            $details->update(['idEstado' => $stateId]);
            $estado = Estado::where('idEstado', $stateId)->get()->first();

            $nCtrl = new CtrlEstadoDelivery();
            $nCtrl->idDetalle = $orderId;
            $nCtrl->idEstado = $stateId;
            $nCtrl->idUsuario = Auth::user()->idUsuario;
            $nCtrl->fechaRegistro = Carbon::now();
            $nCtrl->save();

            return response()->json(
                [
                    'error' => 0,
                    'data' => 'Se cambió el estado del envío a: ' . $estado->descEstado
                ],
                200
            );

        } catch (Exception $ex) {
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

            return response()->json(
                [
                    'error' => 0,
                    'data' => 'Reserva finalizada correctamente.'
                ],
                200
            );

        } catch (Exception $ex) {
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

            return response()->json(
                [
                    'error' => 0,
                    'message' => 'Delivery anulada correctamente.',

                ],
                200
            );

        } catch (Exception $ex) {
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

            return response()->json(
                [
                    'codError' => 0,
                    'messageError' => null,
                    'message' => 'la entrega se registró correctamente'
                ],
                200
            );
        } catch (Exception $ex) {
            Log::error($ex->getMessage(), ['context' => $ex->getTrace()]);
            return response()->json(['codError' => 1, 'messageError' => $ex->getMessage()], 500);
        }

    }

    /*****
     * FOR TEST FUNCTIONS
     *****/

    public function testContractFormat(Request $request)
    {
        $delivery = Delivery::where('idDelivery', $request->idDelivery)->get()->first();
        $orderDelivery = DetalleDelivery::where('idDelivery', $delivery->idDelivery)->get();

        return view('deliveryContract', compact('delivery', 'orderDelivery'));

    }

    /*public function testReserveFormat(Request $request)
    {
        $delivery = Delivery::where('idDelivery', $request->idDelivery)->get()->first();
        $orderDelivery = DetalleDelivery::where('idDelivery', $delivery->idDelivery)->get();

        return view('applicationSheet', compact('delivery', 'orderDelivery'));

    }*/

    public function testSendMail(Request $request)
    {
        $idDelivery = $request->idDelivery;
        $this->sendmail('jylrivera96@gmail.com', $idDelivery);
    }

    /****
     * FUNCTIONS FOR MAIL SENDING
     ****/

    public function sendChangeNotification($mail, $idDelivery)
    {
        $delivery = Delivery::where('idDelivery', $idDelivery)->get()->first();
        $data["email"] = $mail;
        $data["client_name"] = $delivery->nomCliente;
        $data["subject"] = 'Xplore Delivery - Cambio de Hora';
        $data["delivery"] = $delivery;
        $data["from"] = 'Xplore Delivery';

        try {
            Mail::send('changeNotification', $data, function ($message) use ($data) {
                $message
                    ->from('noreply@xplorerentacar.com', $data["from"])
                    ->to($data["email"], $data["client_name"])
                    ->subject($data["subject"]);
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

    public function sendmail($mail, $idDelivery)
    {
        $delivery = Delivery::where('idDelivery', $idDelivery)->get()->first();
        $data["email"] = $mail;
        $data["client_name"] = $delivery->nomCliente;
        $data["subject"] = 'Xplore Delivery No. ' . $delivery->idDelivery;
        $data["delivery"] = $delivery;
        $orders = DetalleDelivery::where('idDelivery', $idDelivery)->get();
        $data["orderDelivery"] = $orders;
        $data["from"] = 'Xplore Delivery';


        try {
            Mail::send('applicationSheet', $data, function ($message) use ($data) {
                $message
                    ->from('noreply@xplorerentacar.com', $data["from"])
                    ->to($data["email"], $data["client_name"])
                    ->subject($data["subject"]);
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

    /****
     * REPORTS ORDER BY DRIVER
     ****/

    public function reportOrdersByDriver(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.driverId' => 'required',
            'form.initDate' => 'required',
            'form.finDate' => 'required'
        ]);

        $form = $request->form;
        $driver = $form['driverId'];
        if ($driver != -1) {
            $driverDetails = User::where('idUsuario', $driver)->get()->first();
        }

        $initDate = date('Y-m-d', strtotime($form['initDate']));
        $finDate = date('Y-m-d', strtotime($form['finDate']));
        $isSameDay = $initDate == $finDate;
        $initDateTime = new Carbon(date('Y-m-d', strtotime($form['initDate'])) . ' 00:00:00');
        $finDateTime = new Carbon(date('Y-m-d', strtotime($form['finDate'])) . ' 23:59:59');

        try {
            $outputData = [];
            $drivers = User::where('idPerfil', 7)->get();

            if ($driver == -1 && $isSameDay) {
                $orders = DetalleDelivery::where('idEstado', 44)
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])->get();

                foreach ($drivers as $driver) {

                    foreach ($orders as $order) {
                        $dataObj = (object)array();
                        $dataObj->fecha = Carbon::parse($order->fechaEntrega)->format('Y-m-d');
                        $dataObj->driver = $driver->nomUsuario;
                        $dataObj->orders = DetalleDelivery::whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                            ->where('idConductor', $driver->idUsuario)->count();

                        if ($dataObj->orders > 0) {
                            $exist = 0;
                            foreach ($outputData as $output) {
                                if ($dataObj->driver == $output->driver) {
                                    $exist++;
                                }
                            }

                            if ($exist == 0) {
                                array_push($outputData, $dataObj);
                            }

                        }

                    }

                }


                return response()->json(
                    [
                        'error' => 0,
                        'data' => $outputData
                    ],
                    200
                );

            } else if ($driver == -1 && !$isSameDay) {
                $orders = DetalleDelivery::where('idEstado', 44)
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])->orderBy('fechaEntrega', 'desc')->get()
                    ->groupBy(function ($val) {
                        return Carbon::parse($val->fechaEntrega)->format('Y-m-d');
                    });

                foreach ($drivers as $driver) {
                    foreach ($orders as $order) {
                        for ($i = 0; $i < sizeof($order); $i++) {
                            if ($driver->idUsuario == $order[$i]->idConductor) {
                                $dataObj = (object)array();
                                $dataObj->driver = $driver->nomUsuario;
                                $dataObj->fecha = Carbon::parse($order[$i]->fechaEntrega)->format('Y-m-d');
                                $initDateTime = new Carbon(date('Y-m-d', strtotime($dataObj->fecha)) . ' 00:00:00');
                                $finDateTime = new Carbon(date('Y-m-d', strtotime($dataObj->fecha)) . ' 23:59:59');
                                $dataObj->orders = DetalleDelivery::whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                                    ->where('idConductor', $driver->idUsuario)->count();
                                $exist = 0;
                                foreach ($outputData as $output) {
                                    if ($dataObj->fecha == $output->fecha && $dataObj->driver == $output->driver) {
                                        $exist++;
                                    }
                                }

                                if ($exist == 0) {
                                    array_push($outputData, $dataObj);
                                }

                            }

                        }

                    }

                }

                return response()->json(
                    [
                        'error' => 0,
                        'data' => $outputData
                    ],
                    200
                );


            } else if ($driver != -1 && $isSameDay) {
                $orders = DetalleDelivery::where('idEstado', 44)->where('idConductor', $driver)
                    ->whereDate('fechaEntrega', $initDate)->get();

                if (sizeof($orders) > 0) {
                    foreach ($orders as $order) {
                        $dataObj = (object)array();
                        $dataObj->driver = $driverDetails->nomUsuario;
                        $dataObj->fecha = Carbon::parse($order->fechaEntrega)->format('Y-m-d');
                        $dataObj->orders = sizeof($orders);

                        $exist = 0;
                        foreach ($outputData as $output) {
                            if ($dataObj->fecha == $output->fecha && $dataObj->driver == $output->driver) {
                                $exist++;
                            }
                        }

                        if ($exist == 0) {
                            array_push($outputData, $dataObj);
                        }
                    }
                }

                return response()->json(
                    [
                        'error' => 0,
                        'data' => $outputData
                    ],
                    200
                );


            } else {
                $orders = DetalleDelivery::where('idEstado', 44)->where('idConductor', $driver)
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])->orderBy('fechaEntrega', 'desc')->get()
                    ->groupBy(function ($val) {
                        return Carbon::parse($val->fechaEntrega)->format('Y-m-d');
                    });

                foreach ($orders as $order) {

                    for ($i = 0; $i < sizeof($order); $i++) {
                        $data = (object)array();
                        $data->driver = $driverDetails->nomUsuario;
                        $data->fecha = Carbon::parse($order[$i]->fechaEntrega)->format('Y-m-d');
                        $data->orders = sizeof($order);
                    }
                    array_push($outputData, $data);
                }

                return response()->json(
                    [
                        'error' => 0,
                        'data' => $outputData
                    ],
                    200
                );
            }

        } catch (Exception $ex) {
            return response()->json(
                ['error' => 1,
                    'message' => $ex->getMessage()],
                500
            );
        }

    }

    public function reportOrdersByCustomer(Request $request)
    {
        $request->validate([
            'form' => 'required',
            'form.customerId' => 'required',
            'form.initDate' => 'required',
            'form.finDate' => 'required'
        ]);

        $form = $request->form;
        $customer = $form['customerId'];
        if ($customer != -1) {
            $customerDetails = DeliveryClient::where('idCliente', $customer)->get()->first();
        }

        $initDate = date('Y-m-d', strtotime($form['initDate']));
        $finDate = date('Y-m-d', strtotime($form['finDate']));
        $isSameDay = $initDate == $finDate;
        $initDateTime = new Carbon(date('Y-m-d', strtotime($form['initDate'])) . ' 00:00:00');
        $finDateTime = new Carbon(date('Y-m-d', strtotime($form['finDate'])) . ' 23:59:59');

        try {
            $outputData = [];
            $customers = DeliveryClient::where('isActivo', 1)->get();

            $categories = Category::where('isActivo',1)->get();
            $ordersByCatArray = [];

            foreach ($categories as $category) {
                $mydataObj = (object)array();
                $mydataObj->category = $category->descCategoria;
                $mydataObj->orders = DetalleDelivery::where('idEstado', 44)
                    ->whereHas('delivery', function ($q) use ($customerDetails, $category) {
                        $q->where('idCliente', $customerDetails->idCliente)->where('idCategoria', $category->idCategoria);
                    })->count();
                $mydataObj->totalSurcharges = DetalleDelivery::where('idEstado', 44)
                    ->whereHas('delivery', function ($q) use ($customerDetails, $category) {
                        $q->where('idCliente', $customerDetails->idCliente)->where('idCategoria', $category->idCategoria);
                    })->sum('recargo');

                if($mydataObj->orders > 0){
                    $exists = 0;
                    foreach ($outputData as $output) {
                        if ($mydataObj->category == $output->category) {
                            $exists++;
                        }
                    }

                    if ($exists == 0) {
                        array_push($ordersByCatArray, $mydataObj);
                    }
                }
            }

            $totalOrders = DetalleDelivery::where('idEstado', 44)
                ->whereHas('delivery', function ($q) use ($customerDetails) {
                    $q->where('idCliente', $customerDetails->idCliente);
                })->count();

            if ($customer == -1 && $isSameDay) {
                $orders = DetalleDelivery::where('idEstado', 44)
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])->get();

                foreach ($customers as $custr) {

                    foreach ($orders as $order) {
                        $dataObj = (object)array();
                        $dataObj->fecha = Carbon::parse($order->fechaEntrega)->format('Y-m-d');
                        $dataObj->customer = $custr->nomEmpresa;
                        $dataObj->orders = DetalleDelivery::where('idEstado', 44)
                            ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                            ->whereHas('delivery', function ($q) use ($custr) {
                                $q->where('idCliente', $custr->idCliente);
                            })->count();

                        if ($dataObj->orders > 0) {
                            $exist = 0;
                            foreach ($outputData as $output) {
                                if ($dataObj->customer == $output->customer) {
                                    $exist++;
                                }
                            }

                            if ($exist == 0) {
                                array_push($outputData, $dataObj);
                            }

                        }

                    }

                }

                return response()->json(
                    [
                        'error' => 0,
                        'data' => array('ordersReport' => $outputData)
                    ],
                    200
                );

            } else if ($customer == -1 && !$isSameDay) {
                $datedOrders = DetalleDelivery::where('idEstado', 44)
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])->orderBy('fechaEntrega', 'desc')->get()
                    ->groupBy(function ($val) {
                        return Carbon::parse($val->fechaEntrega)->format('Y-m-d');
                    });

                foreach ($customers as $custr) {
                    foreach ($datedOrders as $dOrders) {
                        foreach ($dOrders as $order) {

                            if ($custr->idCliente == $order->delivery->idCliente) {
                                $dataObj = (object)array();
                                $dataObj->customer = $custr->nomEmpresa;
                                $dataObj->fecha = Carbon::parse($order->fechaEntrega)->format('Y-m-d');
                                $initDateTime = new Carbon(date('Y-m-d', strtotime($dataObj->fecha)) . ' 00:00:00');
                                $finDateTime = new Carbon(date('Y-m-d', strtotime($dataObj->fecha)) . ' 23:59:59');
                                $dataObj->orders = DetalleDelivery::where('idEstado', 44)
                                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                                    ->whereHas('delivery', function ($q) use ($custr) {
                                        $q->where('idCliente', $custr->idCliente);
                                    })->count();

                                if ($dataObj->orders > 0) {
                                    $exist = 0;
                                    foreach ($outputData as $output) {
                                        if ($dataObj->fecha == $output->fecha && $dataObj->customer == $output->customer) {
                                            $exist++;
                                        }
                                    }

                                    if ($exist == 0) {
                                        array_push($outputData, $dataObj);
                                    }
                                }


                            }
                        }
                    }

                }

                return response()->json(
                    [
                        'error' => 0,
                        'data' => array(
                            'ordersReport' => $outputData
                        )
                    ],
                    200
                );


            } else if ($customer != -1 && $isSameDay) {
                $orders = DetalleDelivery::where('idEstado', 44)
                    ->whereDate('fechaEntrega', $initDate)->get();

                if (sizeof($orders) > 0) {
                    foreach ($orders as $order) {
                        $dataObj = (object)array();
                        $dataObj->customer = $customerDetails->nomEmpresa;
                        $dataObj->fecha = Carbon::parse($order->fechaEntrega)->format('Y-m-d');
                        $dataObj->orders = DetalleDelivery::where('idEstado', 44)
                            ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                            ->whereHas('delivery', function ($q) use ($customerDetails) {
                                $q->where('idCliente', $customerDetails->idCliente);
                            })->count();

                        if ($dataObj->orders > 0) {
                            $exist = 0;
                            foreach ($outputData as $output) {
                                if ($dataObj->fecha == $output->fecha && $dataObj->customer == $output->customer) {
                                    $exist++;
                                }
                            }

                            if ($exist == 0) {
                                array_push($outputData, $dataObj);
                            }
                        }


                    }
                }

                return response()->json(
                    [
                        'error' => 0,
                        'data' => array(
                            'ordersReport' => $outputData,
                            'totalOrders' => $totalOrders,
                            'ordersByCategory' => $ordersByCatArray
                        )
                    ],
                    200
                );


            } else {
                $orders = DetalleDelivery::where('idEstado', 44)
                    ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                    ->orderBy('fechaEntrega', 'desc')->get()
                    ->groupBy(function ($val) {
                        return Carbon::parse($val->fechaEntrega)->format('Y-m-d');
                    });

                foreach ($orders as $order) {

                    for ($i = 0; $i < sizeof($order); $i++) {
                        $data = (object)array();
                        $data->customer = $customerDetails->nomEmpresa;
                        $data->fecha = Carbon::parse($order[$i]->fechaEntrega)->format('Y-m-d');
                        $initDateTime = new Carbon(date('Y-m-d', strtotime($data->fecha)) . ' 00:00:00');
                        $finDateTime = new Carbon(date('Y-m-d', strtotime($data->fecha)) . ' 23:59:59');
                        $data->orders = DetalleDelivery::where('idEstado', 44)
                            ->whereBetween('fechaEntrega', [$initDateTime, $finDateTime])
                            ->whereHas('delivery', function ($q) use ($customerDetails) {
                                $q->where('idCliente', $customerDetails->idCliente);
                            })->count();

                    }
                    if ($data->orders > 0) {
                        array_push($outputData, $data);
                    }

                }

                return response()->json(
                    [
                        'error' => 0,
                        'data' => array(
                            'ordersReport' => $outputData,
                            'totalOrders' => $totalOrders,
                            'ordersByCategory' => $ordersByCatArray
                        )
                    ],
                    200
                );
            }

        } catch (Exception $ex) {
            return response()->json(
                ['error' => 1,
                    'message' => $ex->getMessage()],
                500
            );
        }

    }

}
