<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplateFillsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_fills', function (Blueprint $table) {
            
            $table->increments('fill_id');
            $table->integer('template_id');
            $table->string('type_code', 10);
            $table->string('name', 100);
            $table->integer('no')->default(0);

            $table->index('template_id');
            $table->index('type_code');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('template_fills');
    }
}
