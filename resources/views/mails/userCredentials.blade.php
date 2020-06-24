<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Acceso</title>
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
    <img src="{{asset('../img/DELIVERY-fullcolor.png')}}" width="15%">
</div>

<div class="row">
    <h4>Es un placer saludarte, {{$cliente->nomRepresentante}}</h4>
    <p>A continuación te proporcionamos los detalles de acceso a Xplore Delivery.</p>
</div>

<div class="row">
    <h4>Nombre de usuario: </h4>{{$cliente->email}}
</div>

<div class="row">
    <h4>Contraseña: </h4> {{$cliente->numIdentificacion}}
</div>

<div class="row">
    <h4>Por favor, cambia tu contraseña al ingresar por primera vez</h4>
</div>

<div class="row">
    <a href="https://gestionesdelivery.xplorerentacar.com">Puedes acceder haciendo clic aquí</a>
</div>

<!--DELIVERY HEADER GROUP END-->
<hr>

</body>

</html>
