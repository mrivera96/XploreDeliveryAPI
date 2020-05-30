<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCostoToTblDetalleDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblDetalleDelivery', function (Blueprint $table) {
            $table->double('tarifaBase')->nullable();
            $table->double('recargo')->nullable();
            $table->double('cTotal')->nullable();
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
            $table->removeColumn('tarifaBase');
            $table->removeColumn('recargo');
            $table->removeColumn('cTotal');
        });
    }
}
