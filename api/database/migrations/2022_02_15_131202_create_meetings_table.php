<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMeetingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meetings', function (Blueprint $table) {

            // Core
            $table->increments('meeting_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->integer('created_user_id');
            $table->integer('updated_user_id')->nullable();
            
            // Foreign Key
            $table->integer('tag_id')->nullable();

            // Status
            $table->string('meeting_code', 10)->nullable();
            $table->string('status_code', 10);
            $table->string('type_code', 10);
            $table->string('resolution_code', 10)->nullable();
            $table->string('priority_level')->default('MEDIUM');
            $table->boolean('is_publish')->default(false);
            $table->boolean('is_secreted')->default(false);
            
            // Detail
            $table->string('subject', 100);
            $table->text('detail')->nullable();
            $table->string('note', 250)->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->useCurrent();
            $table->string('address', 250)->nullable();
            $table->string('url', 250)->nullable();
            $table->integer('period')->nullable();
            $table->integer('annual')->nullable();
            $table->string('pin', 250)->nullable();
            
            // Index
            $table->index('created_user_id');
            $table->index('updated_user_id');
            $table->index('tag_id');
            $table->index('meeting_code');
            $table->index('status_code');
            $table->index('type_code');
            $table->index('resolution_code');
            $table->index('priority_level');
            $table->index('is_publish');
            $table->index('is_secreted');
            $table->index('period');
            $table->index('annual');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('meetings');
    }
}
