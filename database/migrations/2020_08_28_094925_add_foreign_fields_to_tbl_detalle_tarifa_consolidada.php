<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignFieldsToTblDetalleTarifaConsolidada extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblDetalleTarifaConsolidada', function (Blueprint $table) {
            $table->string('dirEntrega')->nullable();
            $table->double('radioMaximoEntrega')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tblDetalleTarifaConsolidada', function (Blueprint $table) {
            $table->dropColumn('dirEntrega');
            $table->dropColumn('radioMaximoEntrega');
        });
    }
}
