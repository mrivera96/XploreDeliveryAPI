<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGastosToClsValoresFact extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblValoresFactDelivery', function (Blueprint $table) {
            $table->float('gastosReembolsables')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tblValoresFactDelivery', function (Blueprint $table) {
            $table->dropColumn('gastosReembolsables');
        });
    }
}
