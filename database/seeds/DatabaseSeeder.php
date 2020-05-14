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
        $this->call(clsTipoEstados::class);
        $this->call(clsEstados::class);
        $this->call(clsTarifasDelivery::class);
        $this->call(DeliveriesSeeder::class);
    }
}
