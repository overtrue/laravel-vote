<?php

namespace Tests;

use Illuminate\Support\Facades\Event;
use Overtrue\LaravelVote\Events\Voted;
use Overtrue\LaravelVote\Events\UnVoted;
use Overtrue\LaravelVote\Vote;

class FeatureTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        config(['auth.providers.users.model' => User::class]);
    }

    public function testBasicFeatures()
    {
        $user = User::create(['name' => 'overtrue']);
        $post = Post::create(['title' => 'Hello world!']);

        $user->Vote($post);

        Event::assertDispatched(Voted::class, function ($event) use ($user, $post) {
            return $event->Vote->Voteable instanceof Post
                && $event->Vote->user instanceof User
                && $event->Vote->user->id === $user->id
                && $event->Vote->Voteable->id === $post->id;
        });

        $this->assertTrue($user->hasVoted($post));
        $this->assertTrue($post->isVotedBy($user));

        $user->unVote($post);

        Event::assertDispatched(UnVoted::class, function ($event) use ($user, $post) {
            return $event->Vote->Voteable instanceof Post
                && $event->Vote->user instanceof User
                && $event->Vote->user->id === $user->id
                && $event->Vote->Voteable->id === $post->id;
        });
    }

    public function test_unVote_features()
    {
        $user1 = User::create(['name' => 'overtrue']);
        $user2 = User::create(['name' => 'allen']);
        $user3 = User::create(['name' => 'taylor']);

        $post = Post::create(['title' => 'Hello world!']);

        $user2->Vote($post);
        $user3->Vote($post);
        $user1->Vote($post);

        $user1->unVote($post);

        $this->assertFalse($user1->hasVoted($post));
        $this->assertTrue($user2->hasVoted($post));
        $this->assertTrue($user3->hasVoted($post));
    }

    public function test_aggregations()
    {
        $user = User::create(['name' => 'overtrue']);

        $post1 = Post::create(['title' => 'Hello world!']);
        $post2 = Post::create(['title' => 'Hello everyone!']);
        $book1 = Book::create(['title' => 'Learn laravel.']);
        $book2 = Book::create(['title' => 'Learn symfony.']);

        $user->Vote($post1);
        $user->Vote($post2);
        $user->Vote($book1);
        $user->Vote($book2);

        $this->assertSame(4, $user->Votes()->count());
        $this->assertSame(2, $user->Votes()->withType(Book::class)->count());
    }

    public function test_object_Voters()
    {
        $user1 = User::create(['name' => 'overtrue']);
        $user2 = User::create(['name' => 'allen']);
        $user3 = User::create(['name' => 'taylor']);

        $post = Post::create(['title' => 'Hello world!']);

        $user1->Vote($post);
        $user2->Vote($post);

        $this->assertCount(2, $post->Voters);
        $this->assertSame('overtrue', $post->Voters[0]['name']);
        $this->assertSame('allen', $post->Voters[1]['name']);

        // start recording
        $sqls = $this->getQueryLog(function () use ($post, $user1, $user2, $user3) {
            $this->assertTrue($post->isVotedBy($user1));
            $this->assertTrue($post->isVotedBy($user2));
            $this->assertFalse($post->isVotedBy($user3));
        });

        $this->assertEmpty($sqls->all());
    }

    public function test_eager_loading()
    {
        $user = User::create(['name' => 'overtrue']);

        $post1 = Post::create(['title' => 'Hello world!']);
        $post2 = Post::create(['title' => 'Hello everyone!']);
        $book1 = Book::create(['title' => 'Learn laravel.']);
        $book2 = Book::create(['title' => 'Learn symfony.']);

        $user->Vote($post1);
        $user->Vote($post2);
        $user->Vote($book1);
        $user->Vote($book2);

        // start recording
        $sqls = $this->getQueryLog(function () use ($user) {
            $user->load('Votes.Voteable');
        });

        $this->assertSame(3, $sqls->count());

        // from loaded relations
        $sqls = $this->getQueryLog(function () use ($user, $post1) {
            $user->hasVoted($post1);
        });
        $this->assertEmpty($sqls->all());
    }

    public function test_eager_loading_error()
    {
        // hasVoted
        $post1 = Post::create(['title' => 'post1']);
        $post2 = Post::create(['title' => 'post2']);

        $user = User::create(['name' => 'user']);

        $user->Vote($post2);

        $this->assertFalse($user->hasVoted($post1));
        $this->assertTrue($user->hasVoted($post2));

        $user->load('Votes');

        $this->assertFalse($user->hasVoted($post1));
        $this->assertTrue($user->hasVoted($post2));

        // isVotedBy
        $user1 = User::create(['name' => 'user1']);
        $user2 = User::create(['name' => 'user2']);

        $post = Post::create(['title' => 'Hello world!']);

        $user2->Vote($post);

        $this->assertFalse($post->isVotedBy($user1));
        $this->assertTrue($post->isVotedBy($user2));

        $post->load('Votes');

        $this->assertFalse($post->isVotedBy($user1));
        $this->assertTrue($post->isVotedBy($user2));
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
