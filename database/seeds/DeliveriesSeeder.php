<?php

use Illuminate\Database\Seeder;

class DeliveriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        \Illuminate\Support\Facades\DB::unprepared('SET IDENTITY_INSERT tblDeliveries ON');
        \Illuminate\Support\Facades\DB::table('tblDeliveries')->insert([
            'idDelivery' => 1,
            'nomCliente' => 'Ismael Alexander Gutierrez', 'numIdentificacion' => '07031994389462',
            'numCelular' => '94689230', 'fechaReserva' => \Carbon\Carbon::now(),
            'dirRecogida' => 'Aeropuerto Internacional Toncontín, Tegucigalpa, Col. 15 de Septiembre, Tegucigalpa, Honduras',
            'email' => 'jylrivera96@gmail.com', 'idCategoria' => 1, 'idEstado' => 36,
            'tarifaBase' => '120.00', 'recargos' => '50.00', 'total' => '170.00',
        ]);

        \Illuminate\Support\Facades\DB::table('tblDetalleDelivery')->insert([
            'idDelivery' => 1,
            'nFactura' => 'N32975',
            'nomDestinatario' => 'Karla Marisol Izaguirre',
            'numCel' => '9463-6597',
            'direccion' => 'Colonia El Sitio, Tegucigalpa',
            'distancia' => '19.1 Km'
        ]);

        \Illuminate\Support\Facades\DB::table('tblDeliveries')->insert([
            'idDelivery' => 2,
            'nomCliente' => 'Carlos Daniel Almendares', 'numIdentificacion' => '0801199001632',
            'numCelular' => '33946384', 'fechaReserva' => \Carbon\Carbon::now(),
            'dirRecogida' => 'Centro Comercial Los Castaños, Boulevard Morazán, Tegucigalpa, Honduras',
            'email' => 'jylrivera96@gmail.com', 'idCategoria' => 2, 'idEstado' => 36,
            'tarifaBase' => '145.00', 'recargos' => '50.00', 'total' => '195.00'
        ]);


        \Illuminate\Support\Facades\DB::table('tblDetalleDelivery')->insert([
            'idDelivery' => 2,
            'nFactura' => 'T-202006325',
            'nomDestinatario' => 'Pablo Alejandro Pineda Rodriguez',
            'numCel' => '9438-9785',
            'direccion' => 'Residencial Honduras, Tegucigalpa',
            'distancia' => '10.5 Km'
        ]);

        \Illuminate\Support\Facades\DB::table('tblDeliveries')->insert([
            'idDelivery' => 4,
            'nomCliente' => 'Rony Pavon', 'numIdentificacion' => '0801199300151',
            'numCelular' => '9933-0807', 'fechaReserva' => '2020-05-15 19:47:00.000',
            'dirRecogida' => 'Ecovivienda Villa Olímpica, Tegucigalpa, Honduras',
            'email' => 'rony_pavon@hotmail.com', 'idCategoria' => 1, 'idEstado' => 36,
            'tarifaBase' => '120.00', 'recargos' => '150.00', 'total' => '270.00',
        ]);


        \Illuminate\Support\Facades\DB::table('tblDetalleDelivery')->insert([
            'idDelivery' => 4,
            'nFactura' => '0001',
            'nomDestinatario' => 'Alejandra Pavon',
            'numCel' => '9902-3767',
            'direccion' => 'Valle de Ángeles, Honduras',
            'distancia' => '26.2 km'
        ]);

        \Illuminate\Support\Facades\DB::table('tblDeliveries')->insert([
            'idDelivery' => 5,
            'nomCliente' => 'Fernando Ramos', 'numIdentificacion' => '0801198613914',
            'numCelular' => '3395-4713', 'fechaReserva' => '2020-05-15 19:46:00.000',
            'dirRecogida' => 'Valle de Ángeles, Honduras',
            'email' => 'josefferhn@gmail.com', 'idCategoria' => 1, 'idEstado' => 36,
            'tarifaBase' => '120.00', 'recargos' => '400.00', 'total' => '520.00',
        ]);


        \Illuminate\Support\Facades\DB::table('tblDetalleDelivery')->insert([
            'idDelivery' => 5,
            'nFactura' => '123',
            'nomDestinatario' => 'Rony',
            'numCel' => '3395-4713',
            'direccion' => 'Plaza Marie, Tegucigalpa, Honduras',
            'distancia' => '30.2 km'
        ]);

        \Illuminate\Support\Facades\DB::unprepared('SET IDENTITY_INSERT tblDeliveries OFF');
    }
}
