<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <title>Hoja de Solicitud</title>

    <style>
        thead {
            background-color: #eee;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }


        .details {
            margin-left: 10px
        }
    </style>
</head>

<body>

<section>
    <img src="http://190.4.56.14/XploreDeliveryAPI/img/DELIVERY-fullcolor.png" style="width: 20%; margin-left: 0">
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
        <p>Descripción de Categoría: <strong>{{$delivery->category->descripcion}}</strong></p>
    </section>

    <section class="details">
        <p style="line-height: 1">Tarifa Base: <strong> L. {{number_format($delivery->tarifaBase, 2)}}</strong></p>
        <p>Subtotal: <strong>L. {{ number_format($delivery->tarifaBase * $delivery->detalle->count() , 2)}}</strong></p>
        <p>Recargo(s) por distancia: <strong>L. {{number_format($delivery->recargos, 2)}}</strong></p>
        <p>Cargos Extra: <strong>L. {{number_format($delivery->cargosExtra, 2)}}</strong></p>
        <p>Total: <strong>L. {{number_format($delivery->total, 2)}}</strong></p>
    </section>

</section>
<h4>Entregas programadas: </h4>
<hr>
<table border="1">
    <thead>
    <tr>
        <th scope="col">N°</th>
        <th scope="col">N° de Factura o Detalle de Envío</th>
        <th scope="col">Nombre del Destinatario</th>
        <th scope="col">Número Celular del Destinatario</th>
        <th scope="col">Dirección del Destinatario</th>
        <th scope="col">Recargo por Distancia</th>
        <th scope="col">Cargos Extra</th>
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
            <td>L. {{number_format($detail->cargosExtra, 2)}}</td>
            <td>L. {{number_format($detail->cTotal, 2)}}</td>
        </tr>
    @endforeach
    </tbody>

</table>
<br>

<section class="details">
    <p>Programar un servicio te permite seleccionar una ventana de 30 minutos para conectar con un conductor.</p>
    <p>Al comienzo de la ventana (30mins antes de la hora solicitada), nuestro sistema mostrará tu solicitud a los
        conductores disponibles.</p>

    <p><strong>NOTAS GENERALES:</strong></p>
    <p>- Programar un servicio por adelantado no garantiza que serás conectado con un Conductor o que el mismo llegará a
        la hora solicitada.</p>
    <p>- En el caso que no seas conectado con un Conductor, al final de tu ventana de conexión (30mins después de la
        hora solicitada) tendrás la opción de cancelar tu servicio sin cargo adicional.</p>

    <p><strong>NOTAS PARA TRASLADO DE ARTICULOS DEL HOGAR Y LÍNEA BLANCA</strong></p>
    <p>En caso de solicitar el servicio de Delivery para traslado de artículos para el hogar favor tener en
        consideración lo siguiente:</p>
    <p>- A excepción de haberlo seleccionado, el servicio contratado no incluye seguro de transporte.</p>
    <p>- Se considerarán como artículos medianos aquellos artículos con peso entre 100-200 libras y/o voluminosos
        como ser: Refrigeradora, Estufa, Lavadora, Secadora, Roperos Medianos, Camas Matrimoniales-King, Centro de
        Entretenimiento, Elípticas, Caminadoras o similares. (un juego de muebles será considerado como 2 artículos
        medianos)</p>
    <p>- Se considerarán como artículos grandes aquellos artículos con peso igual o mayor a 200libras como ser:
        Refrigeradora Side by Side, Centro de Lavado, Freezers, Roperos Grandes o similares.</p>
    <p>- Para el caso de los artículos grandes y muebles, se recomienda verificar previamente si los mismos caben
        por
        los boquetes de las puertas. No se realizan maniobras para ingresar los artículos sobre techos o a través de
        ventanas; tampoco se desarman puertas ni marcos de puertas.</p>
    <p>- La tarifa base no incluye la entrega del artículo a una segunda planta (excluye edificios con elevador de
        carga); en caso de requerirlo, el servicio deberá ser agregado a la tarifa base del envío.</p>
    <p>- Para el traslado de artículos del hogar y línea blanca, se deberá tomar en cuenta la siguiente “Guía de
        Tiempo Extra de Conductor en Categoría de Pick Up + Auxiliar”. (Tomar en cuenta que la categoría de Camión
        incluye 15 minutos adicionales a la categoría de Pick + Up tanto para la carga como para la descarga de
        artículos)</p>

    <p>Guía de Tiempo Extra de Conductor en Categoría de Pick Up + Auxiliar</p>
    <p>&nbsp; Entrega de 1-2 artículos medianos = 0 mins</p>
    <p>&nbsp; Entrega de 3 artículos medianos = 15mins</p>
    <p>&nbsp; Entrega de 1 artículo grande = 15mins</p>
    <p>&nbsp; Entrega de 2-3 artículos grandes = 30mins</p>
    <p>&nbsp; Entrega de 1-2 artículos grandes + 1-2 artículos medianos = 30mins</p>
    <p>&nbsp; Entrega en edificios o peatonales / 1-2 artículos medianos = 15 mins</p>
    <p>&nbsp; Entrega en edificios o peatonales / 3 artículos medianos = 30mins</p>
    <p>&nbsp; Entrega en edificios o peatonales / 1 artículo grande = 30mins</p>
    <p>&nbsp; Entrega en edificios o peatonales / 2-3 artículos grandes = 60mins</p>
    <p>&nbsp; Entrega en edificios o peatonales / 1-2 artículos grandes + 1-2 artículos medianos = 60mins</p>

    <p><strong>NOTAS PARA MUDANZAS:</strong></p>
    <p>En el caso de solicitar el servicio de Delivery para una mudanza favor tener en consideración lo siguiente:</p>
    <p>- El servicio incluye carga y descarga de los bienes a trasladar de acuerdo a la categoría de vehículo
        solicitada.</p>
    <p>- Una mudanza puede durar mas del tiempo incluido en el envío, por lo que se sugiere estimar o incluir de 30 a 60
        minutos de tiempo adicional de carga.</p>
    <p>- El servicio contratado no incluye seguro de transporte, por lo que en caso de ser requerido, el mismo deberá
        ser solicitado a través de nuestro departamento de servicio al cliente.</p>
    <p>- El servicio contratado no incluye embalaje, empaque, montaje ni desmontaje de los bienes a trasladar. Se
        recomienda desmontar, embalar y tener listo previo a la hora de recogida todos los artículos frágiles y
        mueblería para evitar incurrir en costos por daños a los bienes o en costos por tiempo de carga/espera
        adicional.</p>
</section>

</body>

</html>
