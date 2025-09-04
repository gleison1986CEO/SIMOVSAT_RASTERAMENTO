<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEstoquesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('estoque', function (Blueprint $table) {
             $table->increments('id');
             $table->string('iccid', 750);
             $table->string('chip', 750);
             $table->string('imei', 750);
             $table->string('modelo', 750);
             $table->string('hash', 750);
             $table->string('status', 250);
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
        Schema::dropIfExists('estoques');
    }
}
