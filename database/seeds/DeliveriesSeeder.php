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
            'email' => 'jylrivera96@gmail.com', 'idCategoria' => 1, 'idEstado' => 33,
            'tarifaBase' => '120.00', 'recargos' => '50.00', 'total' => '170.00'
        ]);

        \Illuminate\Support\Facades\DB::table('tblDetalleDelivery')->insert([
            'idDelivery' => 1,
            'nFactura' => 'N32975',
            'nomDestinatario' => 'Karla Marisol Izaguirre',
            'numCel' => '9463-6597',
            'direccion'=> 'Colonia El Sitio, Tegucigalpa',
            'distancia' => '19.1 Km'
        ]);

        \Illuminate\Support\Facades\DB::table('tblDeliveries')->insert([
            'idDelivery' => 2,
            'nomCliente' => 'Carlos Daniel Almendares', 'numIdentificacion' => '0801199001632',
            'numCelular' => '33946384', 'fechaReserva' => \Carbon\Carbon::now(),
            'dirRecogida' => 'Centro Comercial Los Castaños, Boulevard Morazán, Tegucigalpa, Honduras',
            'email' => 'jylrivera96@gmail.com', 'idCategoria' => 3, 'idEstado' => 33,
            'tarifaBase' => '145.00', 'recargos' => '50.00', 'total' => '195.00', 'isPagada' => 1
        ]);
        \Illuminate\Support\Facades\DB::unprepared('SET IDENTITY_INSERT tblDeliveries OFF');


        \Illuminate\Support\Facades\DB::table('tblDetalleDelivery')->insert([
            'idDelivery' => 2,
            'nFactura' => 'T-202006325',
            'nomDestinatario' => 'Pablo Alejandro Pineda Rodriguez',
            'numCel' => '9438-9785',
            'direccion'=> 'Residencial Honduras, Tegucigalpa',
            'distancia' => '10.5 Km'
        ]);
    }
}
