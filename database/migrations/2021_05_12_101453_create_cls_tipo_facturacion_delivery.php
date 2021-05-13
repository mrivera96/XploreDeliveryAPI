<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClsTipoFacturacionDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clsFrecuenciaFactDelivery', function (Blueprint $table) {
            $table->increments('idFrecuenciaFact');
            $table->string('descripcion',50);
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
        Schema::dropIfExists('clsFrecuenciaFactDelivery');
    }
}
