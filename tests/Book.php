<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelVote\Traits\Voteable;

class Book extends Model
{
    use Voteable;

    protected $fillable = ['title'];
}
