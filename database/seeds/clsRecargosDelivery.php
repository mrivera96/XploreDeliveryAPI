<?php

use Illuminate\Database\Seeder;

class clsRecargosDelivery extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \Illuminate\Support\Facades\DB::table('clsRecargosDelivery')->insert([
            ['kilomMinimo' => 0, 'kilomMaximo' => 10.0, 'monto' => 0.00, 'idCliente' => 1],
            ['kilomMinimo' => floatval(10.01), 'kilomMaximo' => 20.00, 'monto' => 50.00, 'idCliente' => 1],
            ['kilomMinimo' => floatval(20.01), 'kilomMaximo' => 30.00, 'monto' => 150.00, 'idCliente' => 1],
            ['kilomMinimo' => floatval(30.01), 'kilomMaximo' => 40.00, 'monto' => 400.00, 'idCliente' => 1],
        ]);
    }
}
