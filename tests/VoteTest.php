<?php

namespace Tests;

use Illuminate\Support\Facades\Event;
use Overtrue\LaravelVote\Events\Voted;

class VoteTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        config(['auth.providers.users.model' => User::class]);
    }

    public function test_user_can_up_vote_for_votable()
    {
        /* @var \Tests\User $user */
        $user = User::create(['name' => 'overtrue']);
        /* @var \Tests\Idea $idea */
        $idea = Idea::create(['title' => 'Add custom votes support']);

        $user->upvote($idea);

        Event::assertDispatched(Voted::class, function ($event) use ($user, $idea) {
            return $event->vote->votable instanceof Idea
                && $event->vote->user instanceof User
                && $event->vote->user->id === $user->id
                && $event->vote->votes === 1
                && $event->vote->votable->id === $idea->id;
        });

        $this->assertTrue($user->hasVoted($idea));
        $this->assertTrue($idea->hasBeenVotedBy($user));
    }

    public function test_user_can_down_vote_for_votable()
    {
        /* @var \Tests\User $user */
        $user = User::create(['name' => 'overtrue']);
        /* @var \Tests\Idea $idea */
        $idea = Idea::create(['title' => 'Add custom votes support']);

        $user->downvote($idea);

        Event::assertDispatched(Voted::class, function ($event) use ($user, $idea) {
            return $event->vote->votable instanceof Idea
                && $event->vote->user instanceof User
                && $event->vote->user->id === $user->id
                && $event->vote->votes === -1
                && $event->vote->votable->id === $idea->id;
        });

        $this->assertTrue($user->hasVoted($idea));
        $this->assertTrue($idea->hasBeenVotedBy($user));
    }

    public function test_user_can_cancel_vote_for_votable()
    {
        /* @var \Tests\User $user */
        $user = User::create(['name' => 'overtrue']);
        /* @var \Tests\Idea $idea */
        $idea = Idea::create(['title' => 'Add custom votes support']);

        $user->downvote($idea);

        $this->assertTrue($user->hasVoted($idea));
        $this->assertTrue($idea->hasBeenVotedBy($user));

        $user->cancelVote($idea);

        $this->assertFalse($user->hasVoted($idea));
        $this->assertFalse($idea->hasBeenVotedBy($user));
    }

    public function test_votable_can_get_total_votes()
    {
        /* @var \Tests\Idea $idea */
        $idea = Idea::create(['title' => 'Add socialite login support.']);

        /* @var \Tests\User $user1 */
        $user1 = User::create(['name' => 'overtrue']);
        /* @var \Tests\User $user2 */
        $user2 = User::create(['name' => 'allen']);
        /* @var \Tests\User $user3 */
        $user3 = User::create(['name' => 'taylor']);
        /* @var \Tests\User $user4 */
        $user4 = User::create(['name' => 'someone']);

        $user1->vote($idea, 1);
        $user2->upvote($idea);
        $user3->upvote($idea);
        $user4->vote($idea, -1);

        $ideas = Idea::withVotesAttributes()->get()->toArray();

        // 2 = 3 - 1
        $this->assertSame(2, $ideas[0]['total_votes']);
        $this->assertSame(3, $ideas[0]['total_upvotes']);
        $this->assertSame(1, $ideas[0]['total_downvotes']);

        $sqls = $this->getQueryLog(function () {
            Idea::withTotalVotes()->withTotalUpvotes()->withTotalDownvotes()->get()->toArray();
        });

        $this->assertCount(1, $sqls);


        // -2 = -1 + -1
        $user1->vote($idea, -1);  // downvote
        $user2->upvote($idea); // upvote
        $user3->downvote($idea); // downvote
        $user4->vote($idea, -1); // downvote

        $ideas = Idea::withVotesAttributes()->get()->toArray();

        $this->assertSame(-2, $ideas[0]['total_votes']);
        $this->assertSame(1, $ideas[0]['total_upvotes']);
        $this->assertSame(3, $ideas[0]['total_downvotes']);
    }

    public function test_voter_can_attach_vote_status_to_votable_collection()
    {
        /* @var \Tests\Idea $idea1 */
        $idea1 = Idea::create(['title' => 'Add socialite login support.']);
        /* @var \Tests\Idea $idea2 */
        $idea2 = Idea::create(['title' => 'Add php8 support.']);
        /* @var \Tests\Idea $idea3 */
        $idea3 = Idea::create(['title' => 'Add qrcode support.']);

        /* @var \Tests\User $user */
        $user = User::create(['name' => 'overtrue']);

        $user->upvote($idea1);
        $user->downvote($idea2);

        $ideas = Idea::oldest('id')->get();

        $user->attachVoteStatus($ideas);

        $ideas = $ideas->toArray();

        // user has up voted idea1
        $this->assertTrue($ideas[0]['has_voted']);
        $this->assertTrue($ideas[0]['has_upvoted']);
        $this->assertFalse($ideas[0]['has_downvoted']);

        // user has down voted idea2
        $this->assertTrue($ideas[1]['has_voted']);
        $this->assertTrue($ideas[1]['has_downvoted']);
        $this->assertFalse($ideas[1]['has_upvoted']);

        // user hasn't voted idea3
        $this->assertFalse($ideas[2]['has_voted']);
        $this->assertFalse($ideas[2]['has_downvoted']);
        $this->assertFalse($ideas[2]['has_upvoted']);
    }

    public function test_votable_can_get_voters()
    {
        /* @var \Tests\User $user1 */
        $user1 = User::create(['name' => 'overtrue']);
        /* @var \Tests\User $user2 */
        $user2 = User::create(['name' => 'allen']);
        /* @var \Tests\User $user3 */
        $user3 = User::create(['name' => 'taylor']);

        /* @var \Tests\User $user4 */
        $user4 = User::create(['name' => 'someone']);

        /* @var \Tests\Idea $idea */
        $idea = Idea::create(['title' => 'Add socialite login support.']);

        $user2->upvote($idea);
        $user3->upvote($idea);
        $user1->upvote($idea);
        $user4->downvote($idea);

        $this->assertCount(4, $idea->voters);

        $user1->cancelVote($idea);
        $this->assertCount(3, $idea->refresh()->voters);
    }

    public function test_can_aggregate_relations()
    {
        $user = User::create(['name' => 'overtrue']);

        $idea1 = Idea::create(['title' => 'Add socialite login support.']);
        $idea2 = Idea::create(['title' => 'Remove unused items.']);
        $feature1 = Feature::create(['title' => 'Add laravel eloquent support.']);
        $feature2 = Feature::create(['title' => 'Add PHP8.']);

        $user->upvote($idea1);
        $user->upvote($idea2);
        $user->upvote($feature1);
        $user->upvote($feature2);

        $this->assertSame(4, $user->votes()->count());
        $this->assertSame(2, $user->votes()->ofType(Idea::class)->count());

        $this->assertSame(1, $idea1->voters()->count());
        $this->assertSame(1, $idea2->voters()->count());
        $this->assertSame(1, $feature1->voters()->count());
        $this->assertSame(1, $feature2->voters()->count());
    }

    public function test_votable_can_eager_loading_voters()
    {
        $user1 = User::create(['name' => 'overtrue']);
        $user2 = User::create(['name' => 'allen']);
        $user3 = User::create(['name' => 'taylor']);

        $idea = Idea::create(['title' => 'Add socialite login support.']);

        $user1->upvote($idea);
        $user2->downvote($idea);

        $this->assertCount(2, $idea->voters);
        $this->assertSame('overtrue', $idea->voters[0]['name']);
        $this->assertSame('allen', $idea->voters[1]['name']);

        // start recording
        $sqls = $this->getQueryLog(function () use ($idea, $user1, $user2, $user3) {
            $this->assertTrue($idea->hasBeenVotedBy($user1));
            $this->assertTrue($idea->hasBeenVotedBy($user2));
            $this->assertFalse($idea->hasBeenVotedBy($user3));
        });

        $this->assertEmpty($sqls->all());
    }

    public function test_voter_can_eager_loading_votables()
    {
        $user = User::create(['name' => 'overtrue']);

        $idea1 = Idea::create(['title' => 'Add socialite login support.']);
        $idea2 = Idea::create(['title' => 'Remove unused items.']);
        $feature1 = Feature::create(['title' => 'Add laravel eloquent support.']);
        $feature2 = Feature::create(['title' => 'Add PHP8.']);

        $user->upvote($idea1);
        $user->upvote($idea2);
        $user->upvote($feature1);
        $user->upvote($feature2);

        // start recording
        $sqls = $this->getQueryLog(function () use ($user) {
            $user->load('votes.votable');
        });

        $this->assertSame(3, $sqls->count());

        // from loaded relations
        $sqls = $this->getQueryLog(function () use ($user, $idea1) {
            $user->hasVoted($idea1);
        });
        $this->assertEmpty($sqls->all());
    }

    protected function getQueryLog(\Closure $callback): \Illuminate\Support\Collection
    {
        $sqls = \collect([]);
        \DB::listen(function ($query) use ($sqls) {
            $sqls->push(['sql' => $query->sql, 'bindings' => $query->bindings]);
        });

        $callback();

        return $sqls;
    }
}
