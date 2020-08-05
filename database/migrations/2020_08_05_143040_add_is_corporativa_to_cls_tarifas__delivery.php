<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsCorporativaToClsTarifasDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clsTarifasDelivery', function (Blueprint $table) {
            $table->boolean('isCorporativa')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clsTarifasDelivery', function (Blueprint $table) {
            $table->dropColumn('isCorporativa');
        });
    }
}
