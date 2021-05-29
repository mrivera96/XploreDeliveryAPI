<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblValoresFactEnvios extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblValoresFactEnvios', function (Blueprint $table) {
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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblValoresFactEnvios');
    }
}
