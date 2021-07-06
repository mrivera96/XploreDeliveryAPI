<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTermsCondsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clsTermsCondsDelivery', function (Blueprint $table) {
            $table->increments('id');
            $table->string('descripcion',30);
            $table->string('valor',255);
            $table->boolean('negrita')->default(0);
            $table->boolean('cursiva')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clsTermsCondsDelivery');
    }
}
