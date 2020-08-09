<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdTarifaToClsHorariosDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clsHorariosDelivery', function (Blueprint $table) {
            $table->integer('idTarifaDelivery')->unsigned()->nullable();
            $table->foreign('idTarifaDelivery')
            ->references('idTarifaDelivery')
            ->on('clsTarifasDelivery');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clsHorariosDelivery', function (Blueprint $table) {
            $table->dropForeign('idTarifaDelivery');
            $table->dropColumn('idTarifaDelivery');
        });
    }
}
