<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelVote\Traits\Voter;

class User extends Model
{
    use Voter;

    protected $fillable = ['name'];
}
