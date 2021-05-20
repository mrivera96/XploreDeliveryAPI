<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblFactDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblFacturacionDelivery', function (Blueprint $table) {
            $table->increments('idFacturacion');
            $table->float('tYK');
            $table->float('cobVehiculo');
            $table->float('servChofer');
            $table->float('recCombustible');
            $table->float('cobTransporte');
            $table->float('isv');
            $table->float('tasaTuris');
            $table->dateTime('fechaFacturacion');
            $table->string('numFactura', 50);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblFacturacionDelivery');
    }
}
