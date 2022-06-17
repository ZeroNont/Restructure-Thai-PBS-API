<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_uploads', function (Blueprint $table) {
            
            // Core
            $table->increments('upload_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->integer('created_user_id');
            $table->integer('updated_user_id')->nullable();
            
            // Main
            $table->string('module_code', 50);
            $table->integer('ref_id')->nullable();
            $table->string('origin_name', 100);
            $table->string('new_name', 100);
            $table->string('file_ext', 10);
            $table->integer('file_size')->nullable();
            $table->string('title', 100);
            $table->string('note', 250)->nullable();

            // Index
            $table->index('created_user_id');
            $table->index('updated_user_id');
            $table->index('module_code');
            $table->index('ref_id');
            $table->index('file_ext');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('file_uploads');
    }
}
