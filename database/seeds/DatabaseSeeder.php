<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(DeliveryCategoriesSeeder::class);
        $this->call(clsTarifasDelivery::class);
        $this->call(tblClientesDeliverySeeder::class);
        $this->call(SucursalesClientesDelivery::class);
        $this->call(UsuariosDeliverySeeder::class);
        //$this->call(DeliveriesSeeder::class);

    }
}
