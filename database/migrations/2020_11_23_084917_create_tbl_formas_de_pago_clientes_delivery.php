<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblFormasDePagoClientesDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblFormasPagoClientesDelivery', function (Blueprint $table) {
            $table->increments('idFormaPago');
            $table->string('token_card')->unique();
            $table->integer('mes');
            $table->integer('anio');
            $table->string('cvv');
            $table->dateTime('fechaRegistro');
            $table->integer('idCliente')->unsigned();  
        });

        Schema::table('tblFormasPagoClientesDelivery', function (Blueprint $table) {
            $table->foreign('idCliente')
            ->references('idCliente')
            ->on('tblClientesDelivery')
            ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblFormasPagoClientesDelivery');
    }
}
