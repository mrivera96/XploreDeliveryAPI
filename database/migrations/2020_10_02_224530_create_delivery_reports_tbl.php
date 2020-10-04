<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryReportsTbl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblReportesDelivery', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idCliente')->unsigned();
            $table->string('correo',100);
            $table->dateTime('fechaRegistro');
            $table->integer('idUsuario')->unsigned();
        });

        Schema::table('tblReportesDelivery', function (Blueprint $table) {
            $table->foreign('idCliente')
                ->references('idCliente')
                ->on('tblClientesDelivery');

            $table->foreign('idUsuario')
                ->references('idUsuario')
                ->on('tblUsuarios');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblReportesDelivery');
    }
}
