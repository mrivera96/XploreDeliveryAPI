<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClsCategoriasDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clsCategoriasDelivery', function (Blueprint $table) {
            $table->increments('idCategoria');
            $table->string('descCategoria', 60);
            $table->boolean('isActivo')->default(1);
            $table->dateTime('fechaAlta');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clsCategoriasDelivery');
    }
}
