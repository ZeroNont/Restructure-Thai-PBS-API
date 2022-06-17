<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class InitializeSeeder extends Seeder
{

    private const POLICY_VERSION = '1.0.0';
    private const PASSWORD = 'ClickNext1234';
    
    public function run()
    {

        // Configure Faker
        $faker = Faker::create();

        // Create Privacy Policy
        DB::connection('main')->table('policies')->insert([
            'policy_version' => self::POLICY_VERSION,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'detail' => $faker->text,
            'is_enabled' => true
        ]);

        DB::table('users')->insert([
            'username' => 'dj_thanos',
            'email' => 'noravee.s@clicknext.com',
            'password' => Hash::make(self::PASSWORD),
            'actor_code' => 'THANOS',
            'is_enabled' => true,
            'full_name' => 'DJ Thanos',
            'confirmed_at' => date('Y-m-d H:i:s'),
            'policy_version' => self::POLICY_VERSION,
            'employee_code' => null,
            'issue_date' => null,
            'expiry_date' => null,
            'rank' => null,
            'institution' => null,
            'department' => null,
            'branch' => null,
			'is_reset' => false
        ]);

    }

}
