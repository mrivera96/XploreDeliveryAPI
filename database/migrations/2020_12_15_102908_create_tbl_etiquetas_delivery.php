<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblEtiquetasDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblEtiquetasDelivery', function (Blueprint $table) {
            $table->increments('idEtiqueta');
            $table->string('descEtiqueta', 100);
            $table->integer('idCliente')->unsigned();
            $table->dateTime('fechaRegistro');
        });

        Schema::table('tblEtiquetasDelivery', function (Blueprint $table) {
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
        Schema::dropIfExists('tblEtiquetasDelivery');
    }
}
