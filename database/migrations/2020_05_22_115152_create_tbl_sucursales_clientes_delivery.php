<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblSucursalesClientesDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblSucursalesClientesDelivery', function (Blueprint $table) {
            $table->increments('idSucursal');
            $table->string('nomSucursal', 80);
            $table->string('numTelefono', 9)->nullable();
            $table->integer('idCliente')->unsigned();
            $table->string('direccion', 255);
            $table->dateTime('fechaAlta');
            $table->boolean('isActivo')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblSucursalesClientesDelivery');
    }
}
