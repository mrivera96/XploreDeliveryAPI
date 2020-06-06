<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCellphoneNumberToTblUsuarios extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblUsuarios', function (Blueprint $table) {
            $table->string('numCelular',9)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tblUsuarios', function (Blueprint $table) {
            $table->dropColumn('numCelular');
        });
    }
}
