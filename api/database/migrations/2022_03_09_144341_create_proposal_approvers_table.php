<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProposalApproversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proposal_approvers', function (Blueprint $table) {
            
            // Core
            $table->increments('prop_apv_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            // Foreign Key
            $table->integer('prop_id');
            $table->integer('user_id');

            // Detail
            $table->string('status_code', 10);
            $table->integer('no')->default(0);
            $table->text('note')->nullable();
            
            // Index
            $table->index('prop_id');
            $table->index('user_id');
            $table->index('status_code');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('proposal_approvers');
    }
}
