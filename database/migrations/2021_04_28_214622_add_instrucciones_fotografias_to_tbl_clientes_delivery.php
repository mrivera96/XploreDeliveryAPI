<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInstruccionesFotografiasToTblClientesDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblClientesDelivery', function (Blueprint $table) {
            $table->string('instFotografias')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tblClientesDelivery', function (Blueprint $table) {
            $table->dropColumn('instFotografias');
        });
    }
}