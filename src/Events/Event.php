<?php

namespace Overtrue\LaravelVote\Events;

use Illuminate\Database\Eloquent\Model;

class Event
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $vote;

    /**
     * Event constructor.
     */
    public function __construct(Model $vote)
    {
        $this->Vote = $vote;
    }
}
