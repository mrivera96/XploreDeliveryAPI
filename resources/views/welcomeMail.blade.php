<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido(a) a Xplore Delivery</title>
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
    <img src="http://190.4.56.14/XploreDeliveryAPI/img/DELIVERY-fullcolor.png" style="width: 20%; margin-left: 0">
</div>

<div class="row">
    <h4>Es un placer saludarte, {{$cliente->nomRepresentante}}</h4>
    <p>Te damos la bienvenida a nuestro servicio. Ya puedes hacer uso de nuestras plataformas para solicitar tu delivery</p>
</div>

<div class="row">
    <a href="https://delivery.xplorerentacar.com">Puedes acceder haciendo clic aquí</a>
</div>

<!--DELIVERY HEADER GROUP END-->
<hr>

</body>

</html>
