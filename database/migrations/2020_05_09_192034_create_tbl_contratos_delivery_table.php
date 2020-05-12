<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblContratosDeliveryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblContratosDelivery', function (Blueprint $table) {
            $table->increments('idContratoDelivery');
            $table->string('numContrato');
            $table->integer('idDelivery')->unsigned();
            $table->integer('idTarifaDelivery')->unsigned();
            $table->integer('idRecargoDelivery')->unsigned();
            $table->string('estado');
            $table->integer('idUsuario')->unsigned() ;
            $table->date('fechaContrato');
            $table->integer('idVehiculo')->unsigned();
        });

        Schema::table('tblContratosDelivery', function (Blueprint $table) {
            $table->foreign('idDelivery')
            ->references('idDelivery')
            ->on('tblDeliveries');

            $table->foreign('idTarifaDelivery')
            ->references('idTarifaDelivery')
            ->on('clsTarifasDelivery');

            $table->foreign('idRecargoDelivery')
            ->references('idRecargoDelivery')
            ->on('clsRecargosDelivery');

            $table->foreign('idUsuario')
            ->references('idUsuario')
            ->on('tblUsuarios');

            $table->foreign('idVehiculo')
            ->references('idVehiculo')
            ->on('tblVehiculos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblContratosDelivery');
    }
}
