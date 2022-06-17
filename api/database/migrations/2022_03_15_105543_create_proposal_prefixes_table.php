<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProposalPrefixesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proposal_prefixes', function (Blueprint $table) {

            // Core
            $table->increments('prop_prefix_id');
            $table->string('name', 100);
            $table->string('level_code', 20);
            $table->integer('no')->default(0);

            // Index
            $table->index('level_code');
            $table->index('no');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('proposal_prefixes');
    }
}
