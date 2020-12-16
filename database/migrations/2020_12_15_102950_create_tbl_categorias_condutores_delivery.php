<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblCategoriasCondutoresDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblCategoriasConductoresDelivery', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idConductor')->unsigned();
            $table->integer('idCategoria')->unsigned();
            $table->dateTime('fechaRegistro');
        });

        Schema::table('tblCategoriasConductoresDelivery', function (Blueprint $table) {
            $table->foreign('idConductor')
            ->references('idUsuario')
            ->on('tblUsuarios');
            $table->foreign('idCategoria')
            ->references('idCategoria')
            ->on('clsCategoriasDelivery');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblCategoriasConductoresDelivery');
    }
}
