<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblClientesDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblClientesDelivery', function (Blueprint $table) {
            $table->increments('idCliente');
            $table->string('nomEmpresa', 80);
            $table->string('nomRepresentante', 80);
            $table->string('numIdentificacion', 14);
            $table->string('numTelefono', 9);
            $table->dateTime('fechaAlta');
            $table->string('email', 50);
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
        //Schema::dropIfExists('tblClientesDelivery');
    }
}
