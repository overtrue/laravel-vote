<?php

namespace Overtrue\LaravelVote;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Overtrue\LaravelVote\Events\Voted;
use Overtrue\LaravelVote\Events\UnVoted;

/**
 * @property \Illuminate\Database\Eloquent\Model $user
 * @property \Illuminate\Database\Eloquent\Model $voter
 * @property \Illuminate\Database\Eloquent\Model $voteable
 */
class Vote extends Model
{
    protected $guarded = [];

    /**
     * @var string[]
     */
    protected $dispatchesEvents = [
        'created' => Voted::class,
        'deleted' => UnVoted::class,
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = \config('vote.votes_table');

        parent::__construct($attributes);
    }

    protected static function boot()
    {
        parent::boot();

        self::saving(function ($vote) {
            $userForeignKey = \config('vote.user_foreign_key');
            $vote->{$userForeignKey} = $vote->{$userForeignKey} ?: auth()->id();

            if (\config('vote.uuids')) {
                $vote->{$vote->getKeyName()} = $vote->{$vote->getKeyName()} ?: (string) Str::orderedUuid();
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function Voteable()
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\config('auth.providers.users.model'), \config('vote.user_foreign_key'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function voter()
    {
        return $this->user();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithType(Builder $query, string $type)
    {
        return $query->where('votable_type', app($type)->getMorphClass());
    }
}
