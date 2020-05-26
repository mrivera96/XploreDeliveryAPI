<?php

use Illuminate\Database\Seeder;

class tblClientesDeliverySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \Illuminate\Support\Facades\DB::table('tblClientesDelivery')->insert([
            'nomEmpresa' => 'Xplore Rent A Car',  'nomRepresentante' =>'Rony Pavón' ,'numIdentificacion' => '0000200711111',
            'numTelefono' => '2276-7130', 'fechaAlta' => \Carbon\Carbon::now(), 'email' => 'jylrivera96@gmail.com'
        ]);
    }
}
