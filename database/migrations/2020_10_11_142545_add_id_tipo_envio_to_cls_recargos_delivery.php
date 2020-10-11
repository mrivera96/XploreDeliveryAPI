<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdTipoEnvioToClsRecargosDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clsRecargosDelivery', function (Blueprint $table) {
            $table->integer('idTipoEnvio')->unsigned()->default(1);
            $table->foreign('idTipoEnvio')
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
        Schema::table('clsRecargosDelivery', function (Blueprint $table) {
            $table->dropColumn('idTipoEnvio');
        });
    }
}
