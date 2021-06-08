<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDatosFactToTblClientesDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblClientesDelivery', function (Blueprint $table) {
            $table->string('razonSocial',100)->nullable();
            $table->string('rtn',20)->nullable();
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
            $table->dropColumn('razonSocial');
            $table->dropColumn('rtn');
        });
    }
}
