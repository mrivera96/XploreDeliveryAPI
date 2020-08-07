<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdCargoExtraIdOpcionToTblDetalleDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblDetalleDelivery', function (Blueprint $table) {
            $table->integer('idCargoExtra')->unsigned()->nullable();
            $table->integer('idDetalleOpcion')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tblDetalleDelivery', function (Blueprint $table) {
            $table->foreign('idCargoExtra')
                ->references('idCargoExtra')
                ->on('tblCargosExtrasDetalleEnvio');

            $table->foreign('idDetalleOpcion')
                ->references('idDetalleOpcion')
                ->on('tblDetalleOpcionesCargosExtras');
        });
    }
}
