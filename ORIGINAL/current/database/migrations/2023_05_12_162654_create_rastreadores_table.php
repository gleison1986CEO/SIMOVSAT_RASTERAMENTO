<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRastreadoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rastreador', function (Blueprint $table) {
            $table->increments('id');
            $table->string('imei', 250);
            $table->string('modelo', 250);
            $table->string('equipamento', 250);
            $table->timestamps('data')->useCurrent = true;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rastreadores');
    }
}
