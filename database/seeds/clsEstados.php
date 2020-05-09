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
        \Illuminate\Support\Facades\DB::table('clsEstados')->insert([
            ['idTipoEstado' => 9, 'descEstado' => 'Reserva', 'isActivo' => 1, 'fechaAlta' => \Carbon\Carbon::now()] ,
            ['idTipoEstado' => 9,'descEstado' => 'Contrato', 'isActivo' => 1, 'fechaAlta' => \Carbon\Carbon::now()],
            ['idTipoEstado' => 9,'descEstado' => 'Anulada', 'isActivo' => 1, 'fechaAlta' => \Carbon\Carbon::now()]
        ]);
    }
}
