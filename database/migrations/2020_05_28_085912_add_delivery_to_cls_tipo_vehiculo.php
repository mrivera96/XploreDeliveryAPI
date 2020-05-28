<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryToClsTipoVehiculo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clsTipoVehiculo', function (Blueprint $table) {
            $table->boolean('delivery')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clsTipoVehiculo', function (Blueprint $table) {
            $table->dropColumn('delivery');
        });
    }
}
