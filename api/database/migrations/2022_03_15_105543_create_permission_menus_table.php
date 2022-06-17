<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permission_menus', function (Blueprint $table) {

            $table->increments('per_id');
            $table->string('actor_code', 30);
            $table->string('menu_code', 30);
            $table->string('func_code', 30);
            $table->boolean('is_enabled')->default(false);
            
            $table->index('actor_code');
            $table->index('menu_code');
            $table->index('func_code');
            $table->index('is_enabled');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('permission_menus');
    }
}
