<?php

use Illuminate\Database\Seeder;

class DeliveryCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \Illuminate\Support\Facades\DB::table('clsCategoriasDelivery')->insert([
            'descCategoria' => 'Turismo', 'fechaAlta' => \Carbon\Carbon::now()
        ]);

        \Illuminate\Support\Facades\DB::table('clsCategoriasDelivery')->insert([
            'descCategoria' => 'Pick-Up', 'fechaAlta' => \Carbon\Carbon::now()
        ]);

        \Illuminate\Support\Facades\DB::table('clsCategoriasDelivery')->insert([
            'descCategoria' => 'Panel', 'fechaAlta' => \Carbon\Carbon::now()
        ]);

    }
}
