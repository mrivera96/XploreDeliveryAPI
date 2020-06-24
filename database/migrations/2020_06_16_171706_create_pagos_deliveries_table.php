<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagosDeliveriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblPagosDeliveries', function (Blueprint $table) {
            $table->increments('idPago');
            $table->dateTime('fechaPago');
            $table->double('monto');
            $table->integer('tipoPago')->unsigned();
            $table->integer('idUsuario')->unsigned();
            $table->dateTime('fechaRegistro')->default(\Carbon\Carbon::now());
            $table->integer('idCliente')->unsigned();
            $table->string('referencia', 20);
            $table->string('banco', 50);
            $table->string('numAutorizacion', 8)->nullable();
        });

        Schema::table('tblPagosDeliveries', function (Blueprint $table) {
            $table->foreign('tipoPago')
                ->references('idTipoPago')
                ->on('clsTiposPago');

            $table->foreign('idUsuario')
                ->references('idUsuario')
                ->on('tblUsuarios');

            $table->foreign('idCliente')
                ->references('idCliente')
                ->on('tblClientesDelivery');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblPagosDeliveries');
    }
}
