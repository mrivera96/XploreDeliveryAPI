<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdTipoServicioToClsCategoriasDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clsCategoriasDelivery', function (Blueprint $table) {
            $table->integer('idTipoServicio')->unsigned()->nullable();
            $table->foreign('idTipoServicio')
                ->references('idTipoServicio')
                ->on('clsTiposServicios');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clsCategoriasDelivery', function (Blueprint $table) {
            $table->dropColumn('idTipoServicio');
            $table->dropForeign('idTipoServicio');
        });
    }
}
