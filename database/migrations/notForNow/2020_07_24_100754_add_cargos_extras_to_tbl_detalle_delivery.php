<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCargosExtrasToTblDetalleDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblDetalleDelivery', function (Blueprint $table) {
            $table->double('cargosExtra')->after('recargo')->default(0.00)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tblDetalleDelivery', function (Blueprint $table) {
            $table->dropColumn('cargosExtra');
        });
    }
}
