<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdRecargoToTblDetalleDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblDetalleDelivery', function (Blueprint $table) {
            $table->integer('idRecargo')->unsigned()->nullable();
            $table->foreign('idRecargo')
                ->references('idRecargo')
                ->on('clsRecargosDelivery');
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
            $table->dropForeign('idRecargo');
            $table->dropColumn('idRecargo');
        });
    }
}
