<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblRecargosClienteDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblRecargosClienteDelivery', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idRecargo')->unsigned();
            $table->integer('idCliente')->unsigned();
            $table->dateTime('fechaRegistro');
        });

        Schema::table('tblRecargosClienteDelivery', function (Blueprint $table) {
           $table->foreign('idRecargo')
               ->references('idRecargo')
               ->on('clsRecargosDelivery');

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
        Schema::dropIfExists('tblRecargosClienteDelivery');
    }
}
