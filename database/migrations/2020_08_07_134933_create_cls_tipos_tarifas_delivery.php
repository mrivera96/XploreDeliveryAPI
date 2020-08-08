<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClsTiposTarifasDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clsTiposTarifasDelivery', function (Blueprint $table) {
            $table->increments('idTipoTarifa');
            $table->string('descTipoTarifa', 100);
            $table->boolean('isActivo')->default(true);
            $table->dateTime('fechaRegistro');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clsTiposTarifasDelivery');
    }
}
