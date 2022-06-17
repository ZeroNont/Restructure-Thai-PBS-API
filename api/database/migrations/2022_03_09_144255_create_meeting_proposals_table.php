<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMeetingProposalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meeting_proposals', function (Blueprint $table) {
            
            // Core
            $table->increments('prop_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->integer('created_user_id');
            $table->integer('updated_user_id')->nullable();

            // Master Data
            $table->string('status_code', 20);
            $table->string('type_code', 20);            
            $table->integer('prop_prefix_id');
            $table->string('level_code', 5);
            
            // Detail            
            $table->string('subject', 100);
            $table->text('text_background')->nullable();
            $table->text('text_rule')->nullable();
            $table->text('text_issue')->nullable();
            
            // Index
            $table->index('created_user_id');
            $table->index('updated_user_id');
            $table->index('prop_prefix_id');
            $table->index('level_code');
            $table->index('status_code');
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
        Schema::dropIfExists('meeting_proposals');
    }
}
