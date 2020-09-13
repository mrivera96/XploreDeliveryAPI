<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClsRubrosDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clsRubrosDelivery', function (Blueprint $table) {
            $table->increments('idRubro');
            $table->string('nomRubro',100);
            $table->string('descRubro', 100)->nullable();
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
        Schema::dropIfExists('clsRubrosDelivery');
    }
}
