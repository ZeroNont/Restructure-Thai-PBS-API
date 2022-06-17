<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ranks', function (Blueprint $table) {

            $table->string('rank_code', 15);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('update_at')->nullable();
            $table->string('name', 100);
            $table->string('parent_code', 100)->nullable();

            // Index
            $table->primary('rank_code');
            $table->index('parent_code');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ranks');
    }
}
