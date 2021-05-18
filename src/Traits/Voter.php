<?php

namespace Overtrue\LaravelVote\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * @property \Illuminate\Database\Eloquent\Collection $votes
 */
trait Voter
{
    public function Vote(Model $object)
    {
        /* @var \Overtrue\LaravelVote\Traits\Voteable $object */
        if (!$this->hasVoted($object)) {
            $vote = app(config('vote.vote_model'));
            $vote->{config('vote.user_foreign_key')} = $this->getKey();

            $object->Votes()->save($vote);
        }
    }

    public function unVote(Model $object)
    {
        /* @var \Overtrue\LaravelVote\Traits\Voteable $object */
        $relation = $object->Votes()
            ->where('Voteable_id', $object->getKey())
            ->where('votable_type', $object->getMorphClass())
            ->where(config('vote.user_foreign_key'), $this->getKey())
            ->first();

        if ($relation) {
            $relation->delete();
        }
    }

    public function toggleVote(Model $object)
    {
        $this->hasVoted($object) ? $this->unVote($object) : $this->Vote($object);
    }

    /**
     * @return bool
     */
    public function hasVoted(Model $object)
    {
        return ($this->relationLoaded('Votes') ? $this->Votes : $this->Votes())
            ->where('Voteable_id', $object->getKey())
            ->where('votable_type', $object->getMorphClass())
            ->count() > 0;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function Votes()
    {
        return $this->hasMany(config('vote.vote_model'), config('vote.user_foreign_key'), $this->getKeyName());
    }

    /**
     * Get Query Builder for Votes
     *
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function getVoteItems(string $model)
    {
        return app($model)->whereHas(
            'Voters',
            function ($q) {
                return $q->where(config('vote.user_foreign_key'), $this->getKey());
            }
        );
    }
}
