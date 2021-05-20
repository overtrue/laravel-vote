<?php

namespace Overtrue\LaravelVote\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property \Illuminate\Database\Eloquent\Collection $voters
 * @property \Illuminate\Database\Eloquent\Collection $votes
 * @method bool   relationLoaded(string $name)
 * @method static withVotesAttributes()
 * @method static withTotalVotes()
 * @method static withTotalUpVotes()
 * @method static withTotalDownVotes()
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

    public function upVotes(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->votes()->where('votes', '>', 0);
    }

    public function downVotes(): \Illuminate\Database\Eloquent\Relations\MorphMany
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
        )->where('votable_type', $this->getMorphClass());
    }

    public function upVoters(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->voters()->where('votes', '>', 0);
    }

    public function downVoters(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->voters()->where('votes', '<', 0);
    }

    public function appendsVotesAttributes($attributes = ['total_votes', 'total_up_votes', 'total_down_votes'])
    {
        $this->append($attributes);

        return $this;
    }

    public function getTotalVotesAttribute()
    {
        return abs($this->attributes['total_votes'] ?? $this->totalVotes());
    }

    public function getTotalUpVotesAttribute()
    {
        return abs($this->attributes['total_up_votes'] ?? $this->totalUpVotes());
    }

    public function getTotalDownVotesAttribute()
    {
        return abs($this->attributes['total_down_votes'] ?? $this->totalDownVotes());
    }

    public function totalVotes()
    {
        return abs($this->votes()->sum('votes'));
    }

    public function totalUpVotes()
    {
        return $this->votes()->where('votes', '>', 0)->sum('votes');
    }

    public function totalDownVotes()
    {
        return $this->votes()->where('votes', '<', 0)->sum('votes');
    }

    public function scopeWithTotalVotes(Builder $builder): Builder
    {
        return $builder->withSum('votes as total_votes', 'votes');
    }

    public function scopeWithTotalUpVotes(Builder $builder): Builder
    {
        return $builder->withSum(['votes as total_up_votes' => fn ($q) => $q->where('votes', '>', 0)], 'votes');
    }

    public function scopeWithTotalDownVotes(Builder $builder): Builder
    {
        return $builder->withSum(['votes as total_down_votes' => fn ($q) => $q->where('votes', '<', 0)], 'votes');
    }

    public function scopeWithVotesAttributes(Builder $builder)
    {
        $this->scopeWithTotalVotes($builder);
        $this->scopeWithTotalUpVotes($builder);
        $this->scopeWithTotalDownVotes($builder);
    }
}
