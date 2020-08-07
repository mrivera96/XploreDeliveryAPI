<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblFotografiasDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblFotografiasDelivery', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('idDetalle')->unsigned();
            $table->string('rutaFotografia');
        });

        Schema::table('tblFotografiasDelivery', function (Blueprint $table) {
            $table->foreign('idDetalle')
                ->references('idDetalle')
                ->on('tblDetalleDelivery');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblFotografiasDelivery');
    }
}
