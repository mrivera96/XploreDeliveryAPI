<?php

use Illuminate\Database\Seeder;

class UsuariosDeliverySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $pass = "uXPlore20";

        \Illuminate\Support\Facades\DB::table('tblUsuarios')->insert([
            'nomUsuario' => 'Usuario Xplore', 'passUsuario' => 'NÏÂÒµÍÉÌÏÂÒµÍÉº', 'nickUsuario' => 'uXplore',
            'fechaCreacion' => \Carbon\Carbon::now(), 'idCliente' => 1, 'idPerfil' => 8, 'isActivo' => 1
        ]);
    }

}
