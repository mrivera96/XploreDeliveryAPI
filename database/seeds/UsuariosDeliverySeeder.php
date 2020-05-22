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
        $pass = $this->encriptar("uXPlore20");

        \Illuminate\Support\Facades\DB::table('tblUsuariosDelivery')->insert([
            'nomUsuario' => 'Usuario Xplore',  'passUsuario' => utf8_encode($pass) ,'nickUsuario' => 'uXplore',
           'fechaAlta' => \Carbon\Carbon::now(), 'idSucursal' => 1
        ]);
    }
    private Function encriptar($iString)
    {
        $pwd = "";

        $IL_LONGI = (int)(strlen($iString) / 2);
        $vl_cadena_conv = substr($iString, -$IL_LONGI) . $iString . substr($iString, 0, $IL_LONGI);

        $IL_LONGI = strlen($vl_cadena_conv);
        $IL_COUNT = 0;
        $IL_SUMA = 0;

        Do {
            $IL_SUMA = $IL_SUMA + ord(substr($vl_cadena_conv, $IL_COUNT, 1));
            $IL_COUNT = $IL_COUNT + 1;

        } While ($IL_COUNT <= $IL_LONGI);

        $IL_BASE = intval($IL_SUMA / $IL_LONGI);
        $IL_COUNT = 0;

        Do {
            $pwd = $pwd . Chr(ord(substr($vl_cadena_conv, $IL_COUNT, 1)) + $IL_BASE);
            $IL_COUNT = $IL_COUNT + 1;
        } While ($IL_COUNT < $IL_LONGI);


        $pwd = Chr($IL_BASE - 15) . $pwd . Chr(2 * $IL_BASE);

        return $pwd;
    }
}
