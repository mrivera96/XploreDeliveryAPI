<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblDeliveries', function (Blueprint $table) {
            $table->increments('idDelivery');
            $table->string('nomCliente', 60);
            $table->string('numIdentificacion',14);
            $table->string('numCelular',9);
            $table->dateTime('fechaReserva');
            $table->string('dirRecogida', 255);
            $table->string('email',50);
            $table->integer('idCategoria')->unsigned();
            $table->integer('idEstado')->unsigned();
            $table->dateTime('fechaAnulado')->nullable();
            $table->integer('usrAnuloReserva')->nullable();
            $table->string('motivoAnul')->nullable();
            $table->double('tarifaBase')->nullable();
            $table->double('recargos')->nullable();
            $table->double('total')->nullable();
            $table->boolean('isPagada')->default(0);
            $table->integer('idCliente')->default(1);

        });

        Schema::table('tblDeliveries', function (Blueprint $table) {
            $table->foreign('idCategoria')
                ->references('idCategoria')
                ->on('clsCategoriasDelivery');
            $table->foreign('idEstado')
                ->references('idEstado')
                ->on('clsEstados');
            $table->foreign('idCliente')
                ->references('idCliente')
                ->on('tblClientesDelivery');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblDeliveries');
    }
}
