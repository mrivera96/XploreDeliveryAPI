<?php

use Illuminate\Database\Seeder;

class SucursalesClientesDelivery extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \Illuminate\Support\Facades\DB::table('tblSucursalesClientesDelivery')->insert([
            'nomSucursal' => 'Oficina Principal Tres Caminos', 'idCliente' => 1,
            'direccion' => 'Col. Tres Caminos, Calle Principal Contiguo a Cooperativa Mixta Médica Tegucigalpa, Francisco Morazán',
            'fechaAlta' => \Carbon\Carbon::now(),
        ]);
    }
}
