<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMontoCoberturaToTblDetalleDelievry extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblCargosExtraEnvios', function (Blueprint $table) {
            $table->float('montoCobertura')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tblCargosExtraEnvios', function (Blueprint $table) {
            $table->dropColumn('montoCobertura');
        });
    }
}
