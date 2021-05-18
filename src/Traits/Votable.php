<?php

namespace Overtrue\LaravelVote\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * @property \Illuminate\Database\Eloquent\Collection $voters
 * @property \Illuminate\Database\Eloquent\Collection $votes
 */
trait Voteable
{
    /**
     * @return bool
     */
    public function isVotedBy(Model $user)
    {
        if (\is_a($user, config('auth.providers.users.model'))) {
            if ($this->relationLoaded('Voters')) {
                return $this->Voters->contains($user);
            }

            return ($this->relationLoaded('Votes') ? $this->Votes : $this->Votes())
                    ->where(\config('vote.user_foreign_key'), $user->getKey())->count() > 0;
        }

        return false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function Votes()
    {
        return $this->morphMany(config('vote.vote_model'), 'Voteable');
    }

    /**
     * Return followers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function Voters()
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            config('vote.votes_table'),
            'Voteable_id',
            config('vote.user_foreign_key')
        )
            ->where('votable_type', $this->getMorphClass());
    }
}
