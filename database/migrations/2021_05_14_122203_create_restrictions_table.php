<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestrictionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clsRestriccionesDelivery', function (Blueprint $table) {
            $table->increments('idRestriccion');
            $table->string('descripcion', 50);
            $table->float('valMinimo')->nullable();
            $table->float('valMaximo')->nullable();
            $table->boolean('isActivo')->default(1);
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
        Schema::dropIfExists('clsRestriccionesDelivery');
    }
}
