<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambio de Hora de Reserva</title>
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

<section>
    <img src="http://190.4.56.14/XploreDeliveryAPIDesa/img/DELIVERY-fullcolor.png" style="width: 20%; margin-left: 0">
</section>

<div class="row">
    <h4>Es un placer saludarte, {{$client_name}}</h4>
    <p>Te notificamos que se ha realizado con éxito el cambio de hora de la reserva No. {{$delivery->idDelivery}} a las {{ \Carbon\Carbon::parse(date('H:i', strtotime($delivery->fechaReserva)))->format('h:i a')}} .</p>
</div>

</body>

</html>