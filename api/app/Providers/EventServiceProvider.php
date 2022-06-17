<?php

namespace App\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \App\Events\UserDeleteEvent::class => [
            \App\Listeners\DeleteUserListener::class,
            \App\Listeners\DestroyInvitationListener::class,
            \App\Listeners\DestroyTokenListener::class
        ]
    ];
}
