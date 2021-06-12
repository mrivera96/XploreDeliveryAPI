<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblValFactCargosExtraEnvios extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblValFactCargosExtraEnvios', function (Blueprint $table) {
            $table->increments('idRegistro');
            $table->integer('idDetalle')->unsigned();
            $table->float('tYK');
            $table->float('cobVehiculo');
            $table->float('servChofer');
            $table->float('recCombustible');
            $table->float('cobTransporte');
            $table->float('isv');
            $table->float('tasaTuris');
            $table->float('gastosReembolsables');
        });

        Schema::table('tblValFactCargosExtraEnvios', function (Blueprint $table) {
            $table->foreign('idDetalle')
                ->references('idDetalle')
                ->on('tblDetalleDelivery');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblValFactCargosExtraEnvios');
    }
}
