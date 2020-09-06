<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnviosCargosExtrasTbl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblCargosExtraEnvios', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idDetalle')->unsigned();
            $table->integer('idCargoExtra')->unsigned();
            $table->integer('idDetalleOpcion')->unsigned()->nullable();        
        });

        Schema::table('tblCargosExtraEnvios', function (Blueprint $table) {
            $table->foreign('idDetalle')
            ->references('idDetalle')
            ->on('tblDetalleDelivery');

            $table->foreign('idCargoExtra')
            ->references('idCargoExtra')
            ->on('tblCargosExtrasDetalleEnvio');

            $table->foreign('idDetalleOpcion')
            ->references('idDetalleOpcion')
            ->on('tblDetalleOpcionesCargosExtras');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblCargosExtraEnvios');
    }
}
