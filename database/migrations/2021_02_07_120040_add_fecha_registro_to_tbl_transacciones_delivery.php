<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFechaRegistroToTblTransaccionesDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblTransaccionesDelivery', function (Blueprint $table) {
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
        Schema::table('tblTransaccionesDelivery', function (Blueprint $table) {
            $table->dropColumn('fechaRegistro');
        });
    }
}
