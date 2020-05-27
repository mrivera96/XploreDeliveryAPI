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
            'nomSucursal' => 'Oficina Principal Tres Caminos', 'idCliente' => 1, 'numTelefono' => '2232-2639',
            'direccion' => 'Xplore Rent-A-Car, Col. Tres Caminos, Calle principal, contiguo a oficinas del IHCAFE, Tegucigalpa',
            'fechaAlta' => \Carbon\Carbon::now(),
        ]);

        \Illuminate\Support\Facades\DB::table('tblSucursalesClientesDelivery')->insert([
            'nomSucursal' => 'Oficina Anillo Periférico', 'idCliente' => 1, 'numTelefono' => '2276-7130',
            'direccion' => 'Xplore Rent a Car, Anillo periférico, desvío a Colonia San Miguel, contiguo a Honduautos',
            'fechaAlta' => \Carbon\Carbon::now(),
        ]);
    }
}
