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
        return $this->votes()->sum('votes');
    }

    public function totalUpVotes()
    {
        return $this->votes()->where('votes', '>', 0)->sum('votes');
    }

    public function totalDownVotes()
    {
        return $this->votes()->where('votes', '<', 0)->sum('votes');
    }

    public function scopeWithTotalVotes(Builder $builder)
    {
        return $builder->addSelect(
            \DB::raw(sprintf(
                'cast(ifnull((select sum(`%s`.`votes`)
                                        from `votes` where %s = `%s`.`votable_id`
                                        and `%s`.`votable_type` = "%s"), 0) as SIGNED)
                            as `total_votes`',
                config('vote.votes_table'),
                $this->getTable() . '.' . $this->getKeyName(),
                config('vote.votes_table'),
                config('vote.votes_table'),
                $this->getTable()
            ))
        );
    }

    public function scopeWithTotalUpVotes(Builder $builder)
    {
        return $builder->addSelect(
            \DB::raw(sprintf(
                'cast(ifnull((select sum(`%s`.`votes`)
                                        from `votes` where `votes` > 0 and %s = `%s`.`votable_id`
                                        and `%s`.`votable_type` = "%s"), 0) as SIGNED)
                            as `total_votes`',
                config('vote.votes_table'),
                $this->getTable() . '.' . $this->getKeyName(),
                config('vote.votes_table'),
                config('vote.votes_table'),
                $this->getTable()
            ))
        );
    }

    public function scopeWithTotalDownVotes(Builder $builder)
    {
        return $builder->addSelect(
            \DB::raw(sprintf(
                'cast(ifnull((select sum(`%s`.`votes`)
                                        from `votes` where `votes` < 0 and %s = `%s`.`votable_id`
                                        and `%s`.`votable_type` = "%s"), 0) as SIGNED)
                            as `total_votes`',
                config('vote.votes_table'),
                $this->getTable() . '.' . $this->getKeyName(),
                config('vote.votes_table'),
                config('vote.votes_table'),
                $this->getTable()
            ))
        );
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
