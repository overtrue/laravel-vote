<?php

namespace Overtrue\LaravelVote\Events;

use Overtrue\LaravelVote\Vote;

class Event
{
    public Vote $vote;

    /**
     * Event constructor.
     */
    public function __construct(Vote $vote)
    {
        $this->vote = $vote;
    }
}
