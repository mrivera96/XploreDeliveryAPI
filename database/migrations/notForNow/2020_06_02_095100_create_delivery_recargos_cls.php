<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryRecargosCls extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clsRecargosDelivery', function (Blueprint $table) {
            $table->increments('idRecargo');
            $table->double('KilomMinimo');
            $table->double('KilomMaximo');
            $table->double('monto');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clsRecargosDelivery');
    }
}
