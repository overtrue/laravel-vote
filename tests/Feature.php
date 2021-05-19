<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelVote\Traits\Votable;

class Feature extends Model
{
    use Votable;

    protected $fillable = ['title'];
}
