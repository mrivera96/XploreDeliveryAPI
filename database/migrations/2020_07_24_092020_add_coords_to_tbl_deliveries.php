<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCoordsToTblDeliveries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblDeliveries', function (Blueprint $table) {
            $table->string('coordsOrigen',80)->nullable();
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
            $table->dropColumn('coordsOrigen');
        });
    }
}
