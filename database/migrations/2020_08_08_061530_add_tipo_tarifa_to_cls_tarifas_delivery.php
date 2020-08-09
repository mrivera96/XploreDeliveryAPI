<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoTarifaToClsTarifasDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clsTarifasDelivery', function (Blueprint $table) {
            $table->integer('idTipoTarifa')->unsigned()->nullable();
            $table->foreign('idTipoTarifa')
            ->references('idTipoTarifa')
            ->on('clsTiposTarifasDelivery');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clsTarifasDelivery', function (Blueprint $table) {
            $table->dropForeign('idTipoTarifa');
            $table->dropColumn('idTipoTarifa');
        });
    }
}
