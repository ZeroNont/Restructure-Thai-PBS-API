<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActiveSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('active_sessions', function (Blueprint $table) {

            $table->increments('session_id');
            $table->timestamp('created_at')->useCurrent();
            $table->integer('user_id');
            $table->string('token', 500);
            $table->timestamp('expired_at')->useCurrent();

            // Index
            $table->index('user_id');
            $table->index('token');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('active_sessions');
    }
}
