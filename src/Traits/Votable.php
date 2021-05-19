<?php

namespace Overtrue\LaravelVote\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property \Illuminate\Database\Eloquent\Collection $voters
 * @property \Illuminate\Database\Eloquent\Collection $votes
 * @method relationLoaded(string $name)
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

    public function totalVotes()
    {
        return $this->morphMany(config('vote.vote_model'), 'votable')->selectRaw('SUM(votes) as total')->groupBy('votable_id');
    }

    public function scopeWithTotalVotes(Builder $builder)
    {
        return $builder->withSum('votes as total_votes', 'votes');
    }

    public function scopeWithTotalUpVotes(Builder $builder)
    {
        return $builder->withSum(['votes as total_up_votes' => fn ($q) => $q->where('votes', '>', 0)], 'votes');
    }

    public function scopeWithTotalDownVotes(Builder $builder)
    {
        return $builder->withSum(['votes as total_down_votes' => fn ($q) => $q->where('votes', '<', 0)], 'votes');
    }

    public function voters()
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            config('vote.votes_table'),
            'votable_id',
            config('vote.user_foreign_key')
        )->where('votable_type', $this->getMorphClass());
    }
}
