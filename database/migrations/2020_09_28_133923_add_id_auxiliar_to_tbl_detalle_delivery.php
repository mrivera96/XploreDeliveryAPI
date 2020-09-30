<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdAuxiliarToTblDetalleDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblDetalleDelivery', function (Blueprint $table) {
            $table->integer('idAuxiliar')->unsigned()->nullable();
            $table->foreign('idAuxiliar')
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
        Schema::table('tblDetalleDelivery', function (Blueprint $table) {
            $table->dropForeign('idAuxiliar');
            $table->dropColumn('idAuxiliar');
        });
    }
}
