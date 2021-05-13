<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdFrecuenciaFactToTblClientesDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblClientesDelivery', function (Blueprint $table) {
            $table->integer('idFrecuenciaFact')->unsigned()->nullable();
            $table->foreign('idFrecuenciaFact')
                ->references('idFrecuenciaFact')
                ->on('clsFrecuenciaFactDelivery');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tblClientesDelivery', function (Blueprint $table) {
            $table->dropForeign('idFrecuenciaFact');
            $table->dropColumn('idFrecuenciaFact');
        });
    }
}
