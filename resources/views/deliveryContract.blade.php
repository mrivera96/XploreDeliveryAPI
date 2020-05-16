<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Contrato Delivery</title>
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

        .img-center {
            text-align: center;
        }

        .table-center {

        }

        table {
            margin-left: auto;
            margin-right: auto;
        }

        table, td, th {
            border: 1px solid black;
            border-collapse: collapse;
        }

        .header td {
            width: 350px;
            padding: 5px;
        }
    </style>
</head>
<body>
<div class="row img-center">
    <img src="{{asset('img/LOGO-XPLORE_01-ColorPrimario.png')}}" width="40%">
    <p>Contrato de Arrendamiento con Servicio de Chofer y Combustible Incluido</p>
</div>

<div class="row table-center">

    <table class="header">
        <tr>
            <td>
                <strong>Nombre del Arrendatario:</strong> <br>
                {{$delivery->nomCliente}}
            </td>
            <td>
                <strong>Fecha:</strong> <br>
                {{\Carbon\Carbon::parse($delivery->fechaReserva)->format('j/F/Y h:i a')}}</td>
        </tr>
        <tr>
            <td>
                <strong>N. Identidad / RTN:</strong> <br>
                {{$delivery->numIdentificacion}}
            </td>
            <td>
                <strong>Dirección de Recogida:</strong> <br>
                {{$delivery->dirRecogida}}
            </td>
        </tr>
        <tr>
            <td>
                <strong>Celular:</strong> <br>
                {{$delivery->numCelular}}
            </td>
            <td>
                <strong>Correo Electrónico:</strong> <br>
                {{$delivery->email}}
            </td>
        </tr>
        <tr>
            <td>
                <strong>Categoría Reservada</strong> <br>
                {{$delivery->category->descTipoVehiculo}}
            </td>
            <td>
                <strong>Detalles de Vehículo:</strong>


            </td>
        </tr>
    </table>
</div>

<div class="row">
    <h4>Entregas programadas: </h4>
</div>

<div class="row table-center">
    <table>
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

<div class="row">
    <div class="col">
        <p>El presente contrato se regirá por las Cláusulas siguientes:
            1.- Por una parte, la Sociedad Mercantil Alquiler de Carros, S.A. de C.V. (ALCARSA), que en el presente contrato se denominara “El Arrendador”, y por otra parte
            la persona o entidad cuyo nombre aparece en este contrato que renta el automóvil se denominara “El Arrendatario”.
            2.- La duración del presente contrato es de un día según se determina en este documento, así como el valor por concepto de la renta del automóvil el cual se
            computará en base a cantidad de entregas diarias según la tabla a continuación:</p>
    </div>

    Categoría
    1 - 6 entregas diarias
    Costo por entrega
    7 - 12 entregas diarias
    Costo por entrega
    13 - 20 entregas diarias
    Costo por entrega
    Turismo L. 120.00 L. 100.00 L. 90.00
    Pick Up L. 145.00 L. 125.00 L. 110.00
    Panel L. 170.00 L. 150.00 L. 130.00
    3.- El recargo por entrega se determina según el cuadro a continuación:
    Entregas de 0.00 -10.00 kms
    de distancia del punto inicial
    Entregas de 10.01 - 20.00 kms
    de distancia del punto inicial
    Entregas de 20.01 - 30.00 kms
    de distancia del punto inicial
    Entregas de 30.01 - 40.00 kms de
    distancia del punto inicial
    Recargo L. 0.00 L. 50.00 L. 150.00 L. 400.00
    4.- El automóvil objeto de este Contrato se destinará única y exclusivamente para el transporte de productos de El Arrendatario.
    5.- El Arrendador no será responsable por el olvido, perdida o daño de cualquier objeto de valor que El Arrendatario o cualquier persona deje, almacene o
    transporte en el vehículo objeto de este Contrato, ya sea antes o después de su devolución a El Arrendador, o mientras este en posesión de El Arrendatario, sin
    importar que dicho olvido o daño fuese o no acusado por negligencia, imprudencia, mala fe o descuido. Por el presente Contrato, El Arrendatario asume toda la
    responsabilidad y renuncia a toda reclamación judicial futura que se refiere a daños y perjuicios correspondientes a este caso. Asimismo, El Arrendatario libera
    a El Arrendador de toda responsabilidad judicial por el olvido, perdida o daño de cualquier objeto que pudiera afectar a El Arrendatario o a sus ocupantes.
    6.- El Arrendatario acepta y faculta a El Arrendador a que agregue al precio de la renta el servicio de chofer, la protección contra vuelco y colisión, la protección
    contra accidentes personales, la protección de responsabilidad civil, vidrios y llantas, cobertura 0 deducible, cobertura total, tanque pre-pagado y cualquier otra
    cobertura o servicio adicional que el Arrendatario solicite.
    7.- El Arrendador y El Arrendatario manifiestan que aceptan expresamente todos los datos contenidos en este documento.
    8.- El Arrendatario autoriza a la Sociedad Mercantil Alquiler de Carros, S.A. de C.V. (ALCARSA), a cargar en su tarjeta de crédito o débito el costo por renta del
    vehículo.
    9.- Para toda controversia que se presente con motivo de la interpretación y cumplimiento de este Contrato, las partes se someten a la jurisdicción y
    competencia de las leyes de Honduras y de los tribunales de la ciudad de Tegucigalpa, Municipio del Distrito Central, de la República de Honduras, donde
    tiene su domicilio El Arrendador, y por lo tanto El Arrendatario renuncia a su domicilio. El presente Contrato se suscribe en la República de Honduras, en el
    lugar, fecha y hora de salida indicada en el anverso de ese contrato.

</div>

</div>
</body>
</html>
