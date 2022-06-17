<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invitations', function (Blueprint $table) {
            
            // Core
            $table->increments('invite_id');
            $table->timestamp('created_at')->useCurrent();
            
            // Foreign Key
            $table->integer('user_id');
            
            // Reference
            $table->string('ref_code', 256);
            
            // Status
            $table->timestamp('expired_at')->useCurrent();
            $table->timestamp('confirmed_at')->nullable();

            // Index            
            $table->index('user_id');
            $table->unique('ref_code');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invitations');
    }
}
