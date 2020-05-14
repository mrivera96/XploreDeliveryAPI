<?php

use Illuminate\Database\Seeder;

class clsEstados extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \Illuminate\Support\Facades\DB::unprepared('SET IDENTITY_INSERT clsEstados ON');
        \Illuminate\Support\Facades\DB::table('clsEstados')->insert([
            ['idEstado' => 32,'idTipoEstado' => 8, 'descEstado' => 'Reservación', 'isActivo' => 1, 'fechaAlta' => \Carbon\Carbon::now()] ,
            ['idEstado' => 33,'idTipoEstado' => 8,'descEstado' => 'Contrato', 'isActivo' => 1, 'fechaAlta' => \Carbon\Carbon::now()],
            ['idEstado' => 34,'idTipoEstado' => 8,'descEstado' => 'Anulada', 'isActivo' => 1, 'fechaAlta' => \Carbon\Carbon::now()]
        ]);

        \Illuminate\Support\Facades\DB::unprepared('SET IDENTITY_INSERT clsEstados OFF');

    }
}
