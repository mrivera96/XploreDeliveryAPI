<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblUsuariosDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblUsuariosDelivery', function (Blueprint $table) {
            $table->increments('idUsuario');
            $table->string('nomUsuario', 80);
            $table->string('passUsuario', 255);
            $table->string('nickUsuario', 40)->unique();
            $table->boolean('isActivo')->default(1);
            $table->dateTime('fechaAlta');
            $table->integer('idSucursal')->unsigned();

        });

        Schema::table('tblUsuariosDelivery', function (Blueprint $table){
           $table->foreign('idSucursal')
           ->references('idSucursal')
           ->on('tblSucursalesClientesDelivery')
           ->onUpdate('cascade')
           ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblUsuariosDelivery');
    }
}
