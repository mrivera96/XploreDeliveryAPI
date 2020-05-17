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
            $table->integer('idUsuario')->unsigned();
            $table->date('fechaContrato');
            $table->integer('idVehiculo')->unsigned();
        });

        Schema::table('tblContratosDelivery', function (Blueprint $table) {
            $table->foreign('idDelivery')
                ->references('idDelivery')
                ->on('tblDeliveries')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('idUsuario')
                ->references('idUsuario')
                ->on('tblUsuarios')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('idVehiculo')
                ->references('idVehiculo')
                ->on('tblVehiculos')
                ->onDelete('cascade')
                ->onUpdate('cascade');
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
