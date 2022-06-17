<?php

namespace App\Listeners;

use App\Events\UserDeleteEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class DestroyInvitationListener
{

    public function handle(UserDeleteEvent $event)
    {
        DB::connection('main')->table('invitations')->where('user_id', $event->id)->delete();
    }

}