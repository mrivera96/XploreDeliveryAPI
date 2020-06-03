<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCtrlEstadosDeliveryTbl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblCtrlEstadosDelivery', function (Blueprint $table) {
            $table->increments('idCtrl');
            $table->integer('idDelivery')->unsigned();
            $table->integer('idEstado')->unsigned();
            $table->integer('idUsuario')->unsigned();
            $table->dateTime('fechaRegistro');
        });


        Schema::table('tblCtrlEstadosDelivery', function (Blueprint $table){
           $table->foreign('idDelivery')
           ->references('idDelivery')
           ->on('tblDeliveries');

            $table->foreign('idEstado')
                ->references('idEstado')
                ->on('clsEstados');

            $table->foreign('idUsuario')
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
        Schema::dropIfExists('tblCtrlEstadosDelivery');
    }
}
