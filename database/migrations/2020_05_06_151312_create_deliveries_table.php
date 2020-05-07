<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblDeliveries', function (Blueprint $table) {
            $table->increments('idDelivery');
            $table->string('nomCliente');
            $table->string('numIdentificacion');
            $table->string('numCelular');
            $table->string('fecha');
            $table->string('dirRecogida');
            $table->string('email');
            $table->integer('idCategoria')->unsigned();
        });

        Schema::table('tblDeliveries', function (Blueprint $table) {
            $table->foreign('idCategoria')->references('idTipoVehiculo')->on('clsTipoVehiculo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblDeliveries');
    }
}
