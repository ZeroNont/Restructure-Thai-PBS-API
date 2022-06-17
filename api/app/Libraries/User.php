<?php

namespace App\Libraries;

use Illuminate\Support\Facades\DB;

class User
{

    public static function hasUserAsInput(array $user): bool
    {
        $count = DB::connection('main')->table('users')->where('actor_code', '!=', 'THANOS')->whereIn('user_id', $user)->whereNull('deleted_at')->count();
        return (sizeof($user) === $count) ? true : false;
    }
}
