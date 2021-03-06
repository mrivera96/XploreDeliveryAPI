<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hoja de Solicitud</title>
  <style>
    .column {
      float: left;
      width: 50%;
    }

    /* Clear floats after the columns */
    .row:after {
      content: "";
      display: table;
      clear: both;
    }
  </style>

</head>

<body>

  <div class="row">
    <img src="{{asset('img/logo_resized.png')}}" width="15%">
  </div>

  <div class="row">
    <h4>Es un placer saludarte, {{$delivery->nomCliente}}</h4>
    <p>Hemos recibido tu solicitud de servicio Delivery, y estamos para ayudarte. A continuación
      tienes tu hoja de solicitud.</p>
  </div>

  <div class="row">
    <h3>Solicitud No. {{$delivery->idDelivery}}</h3>
  </div>

  <div class=" row">
      <div class="column">
        <h4>Nombre Completo: </h4>
        <p> {{$delivery->nomCliente}}</p>
      </div>
      <div class="column">
        <h4>No. Identificación:</h4>
        <p> {{$delivery->numIdentificacion}}</p>
      </div>
  </div>
  </div>

  <div class="row">
    <div class="column">
      <h4>Número de celular:
        <p> {{$delivery->numCelular}}</p>
      </h4>
    </div>
    <div class="column">
      <h4>Fecha de recogida:</h4>
      <p> {{\Carbon\Carbon::parse($delivery->fechaReserva)->format('j F, Y')}}</p>
    </div>
  </div>



  <div class="row">
    <div class="column">
      <h4>Hora de recogida:</h4>
      <p> {{\Carbon\Carbon::parse($delivery->fechaReserva)->format('h:i a')}}</p>
    </div>
    <div class="column">
      <h4>Direccion de recogida:</h4>
      <p> {{$delivery->dirRecogida}}</p>
    </div>
  </div>


  <div class="row">
    <div class="column">
      <h4>Correo Electrónico:</h4>
      <p> {{$delivery->email}}</p>
    </div>
    <div class="column">
      <h4>Categoría a reservar:</h4>
      <p>{{$delivery->category->descTipoVehiculo}}</p>
    </div>
  </div>

  <!--DELIVERY HEADER GROUP END-->
  <hr>
  <!--DELIVERY DETAIL GROUP START-->

  <!--ORDERS TABLE START-->
  <div class="row">
    <h4>Entregas programadas: </h4>
  </div>


  <div class="row">
    <table border="1px">
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
  </div>

  <!--ORDERS TABLE END-->

</body>

</html>
