<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTblFormasPagoClientesDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblFormasPagoClientesDelivery', function (Blueprint $table) {
            $table->string('vencimiento');
            $table->dropColumn('mes');
            $table->dropColumn('anio');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tblFormasPagoClientesDelivery', function (Blueprint $table) {
            $table->dropColumn('vencimiento');
            
        });
    }
}
