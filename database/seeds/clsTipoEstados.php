<?php

use Illuminate\Database\Seeder;

class clsTipoEstados extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \Illuminate\Support\Facades\DB::unprepared('SET IDENTITY_INSERT clsTipoEstados ON');

        \Illuminate\Support\Facades\DB::table('clsTipoEstados')->insert([
        'idTipoEstado'=>8,
            'descTipoEstado' => 'XploreDelivery'
        ]);
        \Illuminate\Support\Facades\DB::unprepared('SET IDENTITY_INSERT clsTipoEstados OFF');

    }
}
