<?php

namespace Overtrue\LaravelVote\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property \Illuminate\Database\Eloquent\Collection $voters
 * @property \Illuminate\Database\Eloquent\Collection $votes
 *
 * @method bool   relationLoaded(string $name)
 * @method static withVotesAttributes()
 * @method static withTotalVotes()
 * @method static withTotalUpvotes()
 * @method static withTotalDownvotes()
 */
trait Votable
{
    public function hasBeenVotedBy(Model $user): bool
    {
        if (\is_a($user, config('auth.providers.users.model'))) {
            if ($this->relationLoaded('voters')) {
                return $this->voters->contains($user);
            }

            return ($this->relationLoaded('votes') ? $this->votes : $this->votes())
                    ->where(\config('vote.user_foreign_key'), $user->getKey())->count() > 0;
        }

        return false;
    }

    public function votes(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(config('vote.vote_model'), 'votable');
    }

    public function upvotes(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->votes()->where('votes', '>', 0);
    }

    public function downvotes(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->votes()->where('votes', '<', 0);
    }

    public function voters()
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            config('vote.votes_table'),
            'votable_id',
            config('vote.user_foreign_key')
        )->where('votable_type', $this->getMorphClass())->withPivot(['votes']);
    }

    public function upvoters(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->voters()->where('votes', '>', 0);
    }

    public function downvoters(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->voters()->where('votes', '<', 0);
    }

    public function appendsVotesAttributes($attributes = ['total_votes', 'total_upvotes', 'total_downvotes'])
    {
        $this->append($attributes);

        return $this;
    }

    public function getTotalVotesAttribute()
    {
        return (int) ($this->attributes['total_votes'] ?? $this->totalVotes());
    }

    public function getTotalUpvotesAttribute()
    {
        return abs($this->attributes['total_upvotes'] ?? $this->totalUpvotes());
    }

    public function getTotalDownvotesAttribute()
    {
        return abs($this->attributes['total_downvotes'] ?? $this->totalDownvotes());
    }

    public function totalVotes()
    {
        return $this->votes()->sum('votes');
    }

    public function totalUpvotes()
    {
        return $this->votes()->where('votes', '>', 0)->sum('votes');
    }

    public function totalDownvotes()
    {
        return $this->votes()->where('votes', '<', 0)->sum('votes');
    }

    public function scopeWithTotalVotes(Builder $builder): Builder
    {
        return $builder->withSum(['votes as total_votes' =>
            fn ($q) => $q->select(\DB::raw('COALESCE(SUM(votes), 0)'))
        ], 'votes');
    }

    public function scopeWithTotalUpvotes(Builder $builder): Builder
    {
        return $builder->withSum(['votes as total_upvotes' =>
            fn ($q) => $q->where('votes', '>', 0)->select(\DB::raw('COALESCE(SUM(votes), 0)'))
        ], 'votes');
    }

    public function scopeWithTotalDownvotes(Builder $builder): Builder
    {
        return $builder->withSum(['votes as total_downvotes' =>
            fn ($q) => $q->where('votes', '<', 0)->select(\DB::raw('COALESCE(SUM(votes), 0)'))
        ], 'votes');
    }

    public function scopeWithVotesAttributes(Builder $builder)
    {
        $this->scopeWithTotalVotes($builder);
        $this->scopeWithTotalUpvotes($builder);
        $this->scopeWithTotalDownvotes($builder);
    }
}
