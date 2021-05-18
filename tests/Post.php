<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelVote\Traits\Voteable;

class Post extends Model
{
    use Voteable;

    protected $fillable = ['title'];
}
