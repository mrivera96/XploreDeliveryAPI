<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblTarifasClientesDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblDetalleTarifasDelivery', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idTarifaDelivery')->unsigned();
            $table->integer('idCliente')->unsigned();
            $table->dateTime('fechaRegistro');
        });

        Schema::table('tblDetalleTarifasDelivery', function (Blueprint $table) {
            $table->foreign('idTarifaDelivery')
                ->references('idTarifaDelivery')
                ->on('clsTarifasDelivery');

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
        Schema::dropIfExists('tblDetalleTarifasDelivery');
    }
}
