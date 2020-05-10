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
            $table->integer('idDelivery')->unsigned();
            $table->string('nFactura');
            $table->string('nomDestinatario');
            $table->string('numCel');
            $table->string('direccion');
            $table->string('distancia')->nullable();
            $table->boolean('entregado')->nullable();
            $table->string('horaEntrega')->nullable();
            $table->string('nomRecibio')->nullable();
        });

        Schema::table('tblDetalleDelivery', function (Blueprint $table) {
            $table->foreign('idDelivery')->references('idDelivery')->on('tblDeliveries')->onUpdate('cascade')->onDelete('cascade');
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
