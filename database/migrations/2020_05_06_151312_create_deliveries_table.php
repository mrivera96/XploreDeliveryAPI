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
            $table->dateTime('fechaReserva');
            $table->string('dirRecogida');
            $table->string('email');
            $table->integer('idCategoria')->unsigned();
            $table->integer('idEstado')->unsigned();
            $table->dateTime('fechaAnulado')->nullable();
            $table->integer('usrAnuloReserva')->nullable();
            $table->string('motivoAnul')->nullable();
            $table->double('tarifaBase')->nullable();
            $table->double('recargos')->nullable();
            $table->double('total')->nullable();
            $table->boolean('isPagada')->default(0);

        });

        Schema::table('tblDeliveries', function (Blueprint $table) {
            $table->foreign('idCategoria')->references('idTipoVehiculo')->on('clsTipoVehiculo');
            $table->foreign('idEstado')->references('idEstado')->on('clsEstados');

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
