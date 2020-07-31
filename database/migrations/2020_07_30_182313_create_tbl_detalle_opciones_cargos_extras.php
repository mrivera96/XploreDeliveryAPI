<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblDetalleOpcionesCargosExtras extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblDetalleOpcionesCargosExtras', function (Blueprint $table) {
            $table->increments('idDetalleOpcion');
            $table->integer('idCargoExtra')->unsigned();
            $table->string('descripcion', 100);
            $table->double('costo');
        });

        Schema::table('tblDetalleOpcionesCargosExtras', function (Blueprint $table) {
           $table->foreign('idCargoExtra')
           ->references('idCargoExtra')
           ->on('tblCargosExtrasDetalleEnvio');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblDetalleOpcionesCargosExtras');
    }
}
