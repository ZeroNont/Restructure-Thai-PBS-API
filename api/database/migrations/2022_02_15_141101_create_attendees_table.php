<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendees', function (Blueprint $table) {
            
            // Core
            $table->increments('attendee_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            // Foreign Key
            $table->integer('user_id')->nullable();
            $table->integer('rep_user_id')->nullable();
            $table->integer('pos_id');
            
            // Status
            $table->boolean('is_access')->default(true);
            $table->string('status_code');
            $table->string('out_email', 100)->nullable();
            $table->string('out_ref_code', 256)->nullable();

            // Outsider
            $table->string('out_full_name', 100)->nullable();
            $table->string('out_rank', 100)->nullable();
            $table->string('out_institution', 100)->nullable();

            // Index
            $table->index('user_id');
            $table->index('rep_user_id');
            $table->index('pos_id');
            $table->index('status_code');
            $table->index('out_email');
            $table->index('out_ref_code');
            $table->index('is_access');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendees');
    }
}
