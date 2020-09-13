<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblRubrosClienteDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblRubrosClienteDelivery', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idRubro')->unsigned();
            $table->integer('idCliente')->unsigned();
        });

        Schema::table('tblRubrosClienteDelivery', function (Blueprint $table) {
            $table->foreign('idRubro')
                ->references('idRubro')
                ->on('clsRubrosDelivery');

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
        Schema::dropIfExists('tblRubrosClienteDelivery');
    }
}
