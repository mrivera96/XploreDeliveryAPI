<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRegistradoPorToTblDeliveries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblDeliveries', function (Blueprint $table) {
            $table->integer('registradoPor')->nullable()->unsigned();
            $table->foreign('registradoPor')
                ->references('idUsuario')
                ->on('tblUsuarios');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tblDeliveries', function (Blueprint $table) {
            $table->dropForeign('registradoPor');
            $table->dropColumn('registradoPor');

        });
    }
}
