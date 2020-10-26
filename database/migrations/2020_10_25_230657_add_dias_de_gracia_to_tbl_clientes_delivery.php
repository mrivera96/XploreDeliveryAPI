<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiasDeGraciaToTblClientesDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblClientesDelivery', function (Blueprint $table) {
            $table->integer('diasGracia')->default(5);
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
            $table->dropColumn('diasGracia');
        });
    }
}
