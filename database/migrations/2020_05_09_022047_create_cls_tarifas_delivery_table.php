<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClsTarifasDeliveryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clsTarifasDelivery', function (Blueprint $table) {
            $table->increments('idTarifaDelivery');
            $table->integer('idCategoria')->unsigned();
            $table->integer('entregasMinimas');
            $table->integer('entregasMaximas');
            $table->double('precio');
        });

        Schema::table('clsTarifasDelivery', function (Blueprint $table){
            $table->foreign('idCategoria')
            ->references('idTipoVehiculo')
            ->on('clsTipoVehiculo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clsTarifasDelivery');
    }
}
