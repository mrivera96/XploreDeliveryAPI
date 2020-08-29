<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdCategoriaToClsRecargosDelivery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clsRecargosDelivery', function (Blueprint $table) {
            $table->integer('idCategoria')->unsigned()->nullable();
            $table->dateTime('fechaAlta')->default('2020-06-25 00:00:00');
            $table->boolean('isActivo')->default(1);
        });

        Schema::table('clsRecargosDelivery', function (Blueprint $table) {
            $table->foreign('idCategoria')
            ->references('idCategoria')
            ->on('clsCategoriasDelivery');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clsRecargosDelivery', function (Blueprint $table) {
            $table->dropColumn('idCategoria');
            $table->dropColumn('fechaAlta');
            $table->dropColumn('isActivo');
        });
    }
}
