<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInstruccionesToTblSucursalesClientesDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblSucursalesClientesDelivery', function (Blueprint $table) {
            $table->string('instrucciones')->nullable();
            $table->boolean('isDefault')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tblSucursalesClientesDelivery', function (Blueprint $table) {
            $table->dropColumn('instrucciones');
            $table->dropColumn('default');
        });
    }
}
