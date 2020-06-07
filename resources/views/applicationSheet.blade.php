<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <title>Hoja de Solicitud</title>

    <style>
        thead{
            background-color: #eee;
        }
        table{
          width:100%;
            border-collapse: collapse;
        }
        .details p {
            line-height: 0.6;
        }

        .details {
            margin-left: 10px
        }

    </style>
</head>

<body>

<section>
    <img src="http://190.4.56.14/XploreDeliveryAPI/img/LOGO-XPLORE_01-ColorPrimario.png"
         style="width: 20%; margin-left: 0">
    <p style="line-height: 0.8">Es un placer saludarte, <strong>{{$delivery->nomCliente}}</strong></p>
    <p style="line-height: 0.8">Hemos recibido tu solicitud de Delivery, y estamos para ayudarte. A continuación
        tienes detalles de tu solicitud.</p>

    <h3>Datos de Solicitud</h3>

    <section class="details">
        <p>Nombre: <strong>{{$delivery->nomCliente}}</strong></p>
        <p>Número de Solicitud: <strong>{{$delivery->idDelivery}}</strong></p>
        <p>No. Identificación: <strong> {{$delivery->numIdentificacion}}</strong></p>
    </section>

    <section class="details">
        <p>Número de celular: <strong> {{$delivery->numCelular}}</strong></p>
        <p>Fecha de recogida: <strong> {{\Carbon\Carbon::parse($delivery->fechaReserva)->format('j/m/Y') }}</strong></p>
        <p>Hora de recogida: <strong> {{\Carbon\Carbon::parse($delivery->fechaReserva)->format('h:i a')}}</strong></p>
    </section>

    <section class="details">
        <p style="line-height: 1.2">Dirección de recogida: <strong> {{$delivery->dirRecogida}}</strong></p>
        <p>Correo Electrónico: <strong> {{$delivery->email}}</strong></p>
        <p>Categoría a reservar: <strong>{{$delivery->category->descCategoria}}</strong></p>
    </section>

    <section class="details">
        <p style="line-height: 1">Tarifa Base: <strong> L. {{number_format($delivery->tarifaBase, 2)}}</strong></p>
        <p>Subtotal: <strong>L. {{ number_format($delivery->tarifaBase * $delivery->detalle->count() , 2)}}</strong></p>
        <p>Recargo(s) por distancia: <strong>L. {{number_format($delivery->recargos, 2)}}</strong></p>
        <p>Total: <strong>L. {{number_format($delivery->total, 2)}}</strong></p>
    </section>

</section>
<h4>Entregas programadas: </h4>
<hr>
<table border="1" >
    <thead>
    <tr>
        <th scope="col">N°</th>
        <th scope="col">N° de Factura o Detalle de Envío</th>
        <th scope="col">Nombre del Destinatario</th>
        <th scope="col">Número Celular del Destinatario</th>
        <th scope="col">Dirección del Destinatario</th>
        <th scope="col">Recargo por Distancia</th>
        <th scope="col">Total</th>
    </tr>
    </thead>

    <tbody>
    @foreach($orderDelivery as $detail)
        <tr>
            <td scope="row">{{$loop->index + 1}}</td>
            <td>{{$detail->nFactura}}</td>
            <td>{{$detail->nomDestinatario}}</td>
            <td>{{$detail->numCel}}</td>
            <td>{{$detail->direccion}}</td>
            <td>L. {{number_format($detail->recargo, 2)}}</td>
            <td>L. {{number_format($detail->cTotal, 2)}}</td>
        </tr>
    @endforeach
    </tbody>

</table>
<br>

</body>

</html>
