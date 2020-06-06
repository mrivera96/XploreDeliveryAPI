<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdClienteToClsTarifasDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clsTarifasDelivery', function (Blueprint $table) {
            $table->integer('idCliente')->nullable()->unsigned();
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
        Schema::table('clsTarifasDelivery', function (Blueprint $table) {
            $table->dropColumn('idCliente');
        });
    }
}
