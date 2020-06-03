<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetalleDeliveriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblDetalleDelivery', function (Blueprint $table) {
            $table->increments('idDetalle');
            $table->integer('idDelivery')->unsigned();
            $table->string('nFactura',50);
            $table->string('nomDestinatario', 60);
            $table->string('numCel', 9);
            $table->string('direccion', 255);
            $table->string('distancia', 10)->nullable();
            $table->boolean('entregado')->default(0);
            $table->dateTime('fechaEntrega')->nullable();
            $table->string('nomRecibio', 60)->nullable();
            $table->integer('idConductor')->unsigned()->nullable();
            $table->integer('idEstado')->unsigned()->default(34);
            $table->double('tarifaBase')->nullable();
            $table->double('recargo')->nullable();
            $table->double('cTotal')->nullable();
        });

        Schema::table('tblDetalleDelivery', function (Blueprint $table) {
            $table->foreign('idDelivery')
                ->references('idDelivery')
                ->on('tblDeliveries');
            $table->foreign('idConductor')
                ->references('idUsuario')
                ->on('tblUsuarios');
            $table->foreign('idEstado')
                ->references('idEstado')
                ->on('clsEstados');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblDetalleDelivery');
    }
}
