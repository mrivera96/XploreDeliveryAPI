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
            $table->string('nFactura');
            $table->string('nomDestinatario');
            $table->string('numCel');
            $table->string('direccion');
            $table->string('distancia')->nullable();
            $table->boolean('entregado')->default(0);
            $table->dateTime('fechaEntrega')->nullable();
            $table->string('nomRecibio')->nullable();
            $table->integer('idConductor')->unsigned()->nullable();
        });

        Schema::table('tblDetalleDelivery', function (Blueprint $table) {
            $table->foreign('idDelivery')->references('idDelivery')->on('tblDeliveries')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('idConductor')->references('idUsuario')->on('tblUsuarios');
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
