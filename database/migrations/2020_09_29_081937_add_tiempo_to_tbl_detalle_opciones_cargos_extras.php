<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTiempoToTblDetalleOpcionesCargosExtras extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblDetalleOpcionesCargosExtras', function (Blueprint $table) {
            $table->integer('tiempo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tblDetalleOpcionesCargosExtras', function (Blueprint $table) {
            $table->dropColumn('tiempo');
        });
    }
}
