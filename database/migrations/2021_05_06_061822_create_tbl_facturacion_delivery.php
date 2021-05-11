<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblFacturacionDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblValoresFactDelivery', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idTarifaDelivery')->unsigned()->nullable();
            $table->integer('idCargoExtra')->unsigned()->nullable();
            $table->integer('idDetalleOpcion')->unsigned()->nullable();
            $table->integer('idRecargo')->unsigned()->nullable();
            $table->float('tYK');
            $table->float('cobVehiculo');
            $table->float('servChofer');
            $table->float('recCombustible');
            $table->float('cobTransporte');
            $table->float('isv');
            $table->float('tasaTuris');
        });

        Schema::table('tblValoresFactDelivery', function (Blueprint $table) {
            $table->foreign('idTarifaDelivery')
                ->references('idTarifaDelivery')
                ->on('clsTarifasDelivery')
                ->onDelete('cascade');

            $table->foreign('idCargoExtra')
                ->references('idCargoExtra')
                ->on('tblCargosExtrasDetalleEnvio')
                ->onDelete('cascade');

            $table->foreign('idDetalleOpcion')
                ->references('idDetalleOpcion')
                ->on('tblDetalleOpcionesCargosExtras')
                ->onDelete('cascade');

            $table->foreign('idRecargo')
                ->references('idRecargo')
                ->on('clsRecargosDelivery')
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
        Schema::dropIfExists('tblValoresFactDelivery');
    }
}
