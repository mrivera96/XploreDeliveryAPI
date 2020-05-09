<?php

use Illuminate\Database\Seeder;

class clsTarifasDelivery extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \Illuminate\Support\Facades\DB::table('clsTarifasDelivery')->insert([
            ['idCategoria' => 1, 'entregasMinimas' => 1, 'entregasMaximas' => 6, 'precio' => 120.00],
            ['idCategoria' => 1, 'entregasMinimas' => 7, 'entregasMaximas' => 12, 'precio' => 100.00],
            ['idCategoria' => 1, 'entregasMinimas' => 13, 'entregasMaximas' => 20, 'precio' => 90.00],
            ['idCategoria' => 3, 'entregasMinimas' => 1, 'entregasMaximas' => 6, 'precio' => 145.00],
            ['idCategoria' => 3, 'entregasMinimas' => 7, 'entregasMaximas' => 12, 'precio' => 125.00],
            ['idCategoria' => 3, 'entregasMinimas' => 13, 'entregasMaximas' => 20, 'precio' => 110.00],
            ['idCategoria' => 6, 'entregasMinimas' => 1, 'entregasMaximas' => 6, 'precio' => 170.00],
            ['idCategoria' => 6, 'entregasMinimas' => 7, 'entregasMaximas' => 12, 'precio' => 150.00],
            ['idCategoria' => 6, 'entregasMinimas' => 13, 'entregasMaximas' => 20, 'precio' => 130.00],
        ]);
    }
}
