<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblCargosExtrasDetalleEnvio extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblCargosExtrasDetalleEnvio', function (Blueprint $table) {
            $table->increments('idCargoExtra');
            $table->string('nombre');
            $table->double('costo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblCargosExtrasDetalleEnvio');
    }
}
