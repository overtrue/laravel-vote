<?php

namespace Overtrue\LaravelVote;

use Illuminate\Support\ServiceProvider;

class VoteServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        $this->publishes([
            \dirname(__DIR__).'/config/vote.php' => config_path('vote.php'),
        ], 'laravel-vote-config');

        $this->publishes([
            \dirname(__DIR__).'/migrations/' => database_path('migrations'),
        ], 'laravel-vote-migrations');
    }

    /**
     * Register bindings in the container.
     */
    public function register()
    {
        $this->mergeConfigFrom(
            \dirname(__DIR__).'/config/vote.php',
            'vote'
        );
    }
}
