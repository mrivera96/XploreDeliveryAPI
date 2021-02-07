<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblTransaccionesDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblTransaccionesDelivery', function (Blueprint $table) {
            $table->increments('idTransaccion');
            $table->integer('idDelivery')->unsigned()->nullable();
            $table->integer('idCliente')->unsigned();
            $table->tinyInteger('reasonCode');
            $table->string('reasonCodeDescription',150);
            $table->string('authCode',50)->nullable();
            $table->string('orderNumber',50);
        });

        Schema::table('tblTransaccionesDelivery', function (Blueprint $table) {
            $table->foreign('idCliente')
            ->references('idCliente')
            ->on('tblClientesDelivery');

            $table->foreign('idDelivery')
            ->references('idDelivery')
            ->on('tblDeliveries');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblTransaccionesDelivery');
    }
}
