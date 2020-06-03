<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <title>Hoja de Solicitud</title>
    <link rel="stylesheet" href="{{asset('css/bootstrap.css')}}">
</head>

<body>

<table style="width:100%">
    <tbody>
    <tr>
        <td>
            <img src="{{asset('img/logo_resized.png')}}" width="15%">
        </td>
    </tr>

    <tr>
        <td >
            <h4>Es un placer saludarte, {{$delivery->nomCliente}}</h4>
            <p>Hemos recibido tu solicitud de Delivery, y estamos para ayudarte. A continuación
                tienes tu hoja de solicitud.</p>
        </td>
    </tr>

    <tr>
        <td>
            <h4>Solicitud No. {{$delivery->idDelivery}}</h4>
        </td>
    </tr>


    <tr>
        <td style="width:33%">
            <h4>Nombre Completo: </h4>
            <p> {{$delivery->nomCliente}}</p>
        </td>

        <td style="width:33%">
            <h4>No. Identificación:</h4>
            <p> {{$delivery->numIdentificacion}}</p>
        </td>

        <td style="width:33%">
            <h4>Número de celular:</h4>
            <p> {{$delivery->numCelular}}</p>
        </td>
    </tr>

    <tr>
        <td  style="width:33%">
            <h4>Fecha de recogida:</h4>
            <p> {{\Carbon\Carbon::parse($delivery->fechaReserva)->format('j F, Y')}}</p>
        </td>
        <td  style="width:33%">
            <h4>Hora de recogida:</h4>
            <p> {{\Carbon\Carbon::parse($delivery->fechaReserva)->format('h:i a')}}</p>
        </td>

        <td  style="width:33%">
            <h4>Direccion de recogida:</h4>
            <p> {{$delivery->dirRecogida}}</p>
        </td>
    </tr>
    <tr>
        <td style="width:33%">
            <h4>Correo Electrónico:</h4>
            <p> {{$delivery->email}}</p>
        </td>
        <td style="width:33%">
            <h4>Categoría a reservar:</h4>
            <p>{{$delivery->category->descCategoria}}</p>
        </td>
    </tr>
    <tr>
        <td>
            <h4>Entregas programadas: </h4>
            <hr>
        </td>

    </tr>

    <tr style="width:100%">
        <td>
            <table border="1" style="width:100%">
                <tr>
                    <th scope="col">N°</th>
                    <th scope="col">N° de Factura o Detalle de Envío</th>
                    <th scope="col">Nombre del Destinatario</th>
                    <th scope="col">Número Celular del Destinatario</th>
                    <th scope="col">Dirección del Destinatario</th>
                </tr>

                @foreach($orderDelivery as $detail)
                    <tr>
                        <td scope="row">{{$loop->index + 1}}</td>
                        <td>{{$detail->nFactura}}</td>
                        <td>{{$detail->nomDestinatario}}</td>
                        <td>{{$detail->numCel}}</td>
                        <td>{{$detail->direccion}}</td>
                    </tr>
                @endforeach
            </table>
        </td>

    </tr>

    </tbody>

</table>

</body>

</html>
