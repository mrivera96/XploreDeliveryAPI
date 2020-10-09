<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDistTotalToTblDeliveries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblDeliveries', function (Blueprint $table) {
            $table->float('distTotal')->nullable();
            $table->boolean('isRuteo')->default(false);
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
            $table->dropColumn('distTotal');
            $table->dropColumn('isRuteo');
        });
    }
}
