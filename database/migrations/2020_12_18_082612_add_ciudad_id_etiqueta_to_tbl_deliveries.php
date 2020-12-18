<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCiudadIdEtiquetaToTblDeliveries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblDeliveries', function (Blueprint $table) {
            $table->string('ciudad',80)->nullable();
            $table->integer('idEtiqueta')->unsigned()->nullable();
            $table->foreign('idEtiqueta')
            ->references('idEtiqueta')
            ->on('tblEtiquetasDelivery');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tblDeliveries', function (Blueprint $table) {
            $table->dropColumn('ciudad');
            $table->dropForeign('idEtiqueta');
            $table->dropColumn('idEtiqueta');
        });
    }
}
