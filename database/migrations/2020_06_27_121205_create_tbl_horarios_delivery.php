<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblHorariosDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clsHorariosDelivery', function (Blueprint $table) {
            $table->increments('idHorario');
            $table->string('dia',10);
            $table->tinyInteger('cod');
            $table->time('inicio');
            $table->time('final');
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
        Schema::dropIfExists('clsHorariosDelivery');
    }
}
