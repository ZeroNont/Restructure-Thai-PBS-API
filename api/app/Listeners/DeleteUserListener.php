<?php

namespace App\Listeners;

use App\Events\UserDeleteEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Libraries\Util;

class DeleteUserListener
{

    public function handle(UserDeleteEvent $event)
    {
        DB::connection('main')->table('users')->where('user_id', $event->id)->update([
            'deleted_at' => Util::now(),
            'is_enabled' => false
        ]);
    }

}
