<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            
            // Core
            $table->increments('user_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            // Auth
            $table->integer('created_user_id')->nullable();
            $table->string('username', 100);
            $table->string('email', 100);
            $table->string('mobile_phone', 10)->nullable();
            $table->string('password', 250)->nullable();
            $table->string('pin', 250)->nullable();
            
            // Status
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->timestamp('confirmed_at')->nullable();
            $table->string('policy_version', 10)->nullable();
			$table->boolean('is_reset')->default(false);
			$table->boolean('is_permanent')->default(true);

            // Profile
            $table->string('actor_code', 10);
            $table->string('full_name', 100);
            $table->string('employee_code', 50)->nullable();
            $table->string('rank', 100)->nullable();
            $table->string('rank_code', 15)->nullable();
            $table->string('institution', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('branch', 100)->nullable();

            // Index
            $table->unique('username');
            $table->index('email');
            $table->index('mobile_phone');
            $table->index('is_enabled');
            $table->index('actor_code');
            $table->index('employee_code');
            $table->index('is_reset');
            $table->index('is_permanent');
            $table->index('created_user_id');
            $table->index('rank_code');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
