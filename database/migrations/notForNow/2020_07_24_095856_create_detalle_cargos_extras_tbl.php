<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetalleCargosExtrasTbl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblDetalleCargosExtras', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idCargoExtra')->unsigned();
            $table->integer('idCategoria')->unsigned();
        });

        Schema::table('tblDetalleCargosExtras', function (Blueprint $table) {
            $table->foreign('idCargoExtra')
            ->references('idCargoExtra')
            ->on('tblCargosExtrasDetalleEnvio');

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
        Schema::dropIfExists('tblDetalleCargosExtras')->disableForeignKeyConstraints();
    }
}
