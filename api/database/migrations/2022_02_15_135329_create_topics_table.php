<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTopicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('topics', function (Blueprint $table) {
            
            // Core
            $table->increments('topic_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            
            // Foreign Key
            $table->integer('meeting_id');

            // Detail
            $table->string('topic_no');
            $table->string('subject', 100);
            $table->text('detail')->nullable();
            $table->string('note', 250)->nullable();

            // Voting
            $table->boolean('has_vote')->default(false);
            $table->boolean('is_passed')->default(true);

            // Index
            $table->index('meeting_id');
            $table->index('topic_no');
            $table->index('has_vote');
            $table->index('is_passed');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('topics');
    }
}
