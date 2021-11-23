Laravel Vote
---

⬆️ ⬇️ User vote system for Laravel Application.

[![CI](https://github.com/overtrue/laravel-vote/workflows/CI/badge.svg)](https://github.com/overtrue/laravel-vote/actions/workflows/ci.yml)

[![Sponsor me](https://raw.githubusercontent.com/overtrue/overtrue/master/sponsor-me-button-s.svg)](https://github.com/sponsors/overtrue)


## Installing

```shell
$ composer require overtrue/laravel-vote -vvv
```

### Configuration

This step is optional

```bash
$ php artisan vendor:publish --provider="Overtrue\\LaravelVote\\VoteServiceProvider" --tag=config
```

### Migrations

This step is required, you can publish the migration files:

```bash
$ php artisan vendor:publish --provider="Overtrue\\LaravelVote\\VoteServiceProvider" --tag=migrations
```

then create tables: 

```bash
$ php artisan migrate
```

## Usage

### Traits

#### `Overtrue\LaravelVote\Traits\Voter`

```php

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Overtrue\LaravelVote\Traits\Voter;

class User extends Authenticatable
{
    use Voter;
    
    <...>
}
```

#### `Overtrue\LaravelVote\Traits\Voteable`

```php
use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelVote\Traits\Votable;

class Idea extends Model
{
    use Votable;

    <...>
}
```

### API

```php
$user = User::find(1);
$idea = Idea::find(2);

$user->vote($idea, 1); // upvote
$user->vote($idea, -1); // downvote
$user->upvote($idea);
$user->downvote($idea);

// with custom number of votes
$user->upvote($idea, 3);
$user->downvote($idea, 3);

// cancel vote
$user->cancelVote($idea);

// get my voted items
$user->getVotedItems(Idea::class) // Illuminate\Database\Eloquent\Builder

// state
$user->hasVoted($idea); 
$idea->hasBeenVotedBy($user); 
```

#### Get model voters:

```php
foreach($idea->voters as $user) {
    // echo $user->name;
}
```

#### Get user voted items.

User can easy to get Votable models to do what you want.

*note: this method will return a `Illuminate\Database\Eloquent\Builder` *

```php
$votedItemsQuery = $user->getVotedItems();

// filter votable_type
$votedIdeasQuery = $user->getVotedItems(Idea::class);

// fetch results
$votedIdeas = $user->getVoteItems(Idea::class)->get();
$votedIdeas = $user->getVoteItems(Idea::class)->paginate();
$votedIdeas = $user->getVoteItems(Idea::class)->where('title', 'Laravel-Vote')->get();
```

### Aggregations

### count relations
```php
// all
$user->votes()->count(); 

// filter votable_type
$user->votes()->ofType(Idea::class)->count(); 

// voters count
$idea->voters()->count();
```

List with `*_count` attribute:

```php
// for Voter models:
$users = User::withCount('votes')->get();
// or
$users = User::withCount('upvotes')->get();
// or
$users = User::withCount('downvotes')->get();
// or
$users = User::withCount(['votes', 'upvotes', 'downvotes'])->get();

foreach($users as $user) {
    echo $user->votes_count;
    echo $user->upvotes_count;
    echo $user->downvotes_count;
}

// for Votable models: 
$ideas = Idea::withCount('voters')->get();
// or
$ideas = Idea::withCount('upvoters')->get();
$ideas = Idea::withCount('downvoters')->get();

// or
$ideas = Idea::withCount(['voters', 'upvoters', 'downvoters'])->get();

foreach($ideas as $idea) {
    echo $idea->voters_count;
    echo $idea->upvoters_count;
    echo $idea->downvoters_count;
}
```

### Votable sum votes

```php
$user1->upvote($idea); // 1 (up)
$user2->upvote($idea); // 2 (up)
$user3->upvote($idea); // 3 (up)
$user4->downvote($idea); // -1 (down)

// sum(votes)
$idea->totalVotes(); // 2(3 - 1)

// sum(votes) where votes > 0
$idea->totalUpvotes(); // 3

// abs(sum(votes)) where votes < 0
$idea->totalDownvotes(); // 1

// appends aggregations attributes
$idea->appendsVotesAttributes();
$idea->toArray();
// result
[
    "id" => 1
    "title" => "Add socialite login support."
    "created_at" => "2021-05-20T03:26:16.000000Z"
    "updated_at" => "2021-05-20T03:26:16.000000Z"
    
    // these aggregations attributes will be appends.
    "total_votes" => 2
    "total_upvotes" => 3
    "total_downvotes" => 1
  ],
```

### Attach voter vote status to votable collection

You can use `Voter::attachVoteStatus(Collection $votables)` to attach the voter vote status, it will set `has_voted`,`has_upvoted` and `has_downvoted` attributes to each model of `$votables`:

#### For model
```php
$idea = Idea::find(1);

$user->attachVoteStatus($idea);

// result
[
    "id" => 1
    "title" => "Add socialite login support."
    "created_at" => "2021-05-20T03:26:16.000000Z"
    "updated_at" => "2021-05-20T03:26:16.000000Z"
    "has_voted" => true
    "has_upvoted" => true
    "has_downvoted" => false
 ],
```

#### For `Collection | Paginator | LengthAwarePaginator | array`:

```php
$ideas = Idea::oldest('id')->get();

$user->attachVoteStatus($ideas);

$ideas = $ideas->toArray();

// result
[
  [
    "id" => 1
    "title" => "Add socialite login support."
    "created_at" => "2021-05-20T03:26:16.000000Z"
    "updated_at" => "2021-05-20T03:26:16.000000Z"
    "has_voted" => true
    "has_upvoted" => true
    "has_downvoted" => false
  ],
  [
    "id" => 2
    "title" => "Add php8 support."
    "created_at" => "2021-05-20T03:26:16.000000Z"
    "updated_at" => "2021-05-20T03:26:16.000000Z"
    "has_voted" => true
    "has_upvoted" => false
    "has_downvoted" => true
  ],
  [
    "id" => 3
    "title" => "Add qrcode support."
    "created_at" => "2021-05-20T03:26:16.000000Z"
    "updated_at" => "2021-05-20T03:26:16.000000Z"
    "has_voted" => false
    "has_upvoted" => false
    "has_downvoted" => false
  ],
]
```

#### For pagination

```php
$ideas = Idea::paginate(20);

$user->attachVoteStatus($ideas->getCollection());
```

### N+1 issue

To avoid the N+1 issue, you can use eager loading to reduce this operation to just 2 queries. When querying, you may specify which relationships should be eager loaded using the `with` method:

```php
// Voter
use Tests\Idea;$users = User::with('votes')->get();

foreach($users as $user) {
    $user->hasVoted($idea);
}

// Votable
$ideas = Idea::with('voters')->get();

foreach($ideas as $idea) {
    $idea->hasBeenVotedBy($user);
}

// Votable votes
$ideas = Idea::withTotalVotes() // total_votes
        ->withTotalUpvotes() // total_upvotes
        ->withTotalDownvotes() // total_downvotes
        ->get();

// same as
// withVotesAttributes() = withTotalVotes() + withTotalUpvotes() + withTotalDownvotes() 
$ideas = Idea::withVotesAttributes()->get();

// result
[
  [
    "id" => 1
    "title" => "Add socialite login support."
    "created_at" => "2021-05-19T07:01:10.000000Z"
    "updated_at" => "2021-05-19T07:01:10.000000Z"
    "total_votes" => 2
    "total_upvotes" => 3
    "total_downvotes" => 1
  ],
  [
    "id" => 2
    "title" => "Add PHP8 support."
    "created_at" => "2021-05-20T07:01:10.000000Z"
    "updated_at" => "2021-05-20T07:01:10.000000Z"
    "total_votes" => 1
    "total_upvotes" => 2
    "total_downvotes" => 1
  ]
]
```

### Events

| **Event** | **Description** |
| --- | --- |
|  `Overtrue\LaravelVote\Events\Voted` | Triggered when the relationship is created. |
|  `Overtrue\LaravelVote\Events\VoteCancelled` | Triggered when the relationship is deleted. |

## Related packages

- Follow: [overtrue/laravel-follow](https://github.com/overtrue/laravel-follow)
- Like: [overtrue/laravel-like](https://github.com/overtrue/laravel-like)
- Vote: [overtrue/laravel-vote](https://github.com/overtrue/laravel-Vote)
- Subscribe: [overtrue/laravel-subscribe](https://github.com/overtrue/laravel-subscribe)
- Bookmark: overtrue/laravel-bookmark (working in progress)

## :heart: Sponsor me 

[![Sponsor me](https://raw.githubusercontent.com/overtrue/overtrue/master/sponsor-me.svg)](https://github.com/sponsors/overtrue)

如果你喜欢我的项目并想支持它，[点击这里 :heart:](https://github.com/sponsors/overtrue)


## Project supported by JetBrains

Many thanks to Jetbrains for kindly providing a license for me to work on this and other open-source projects.

[![](https://resources.jetbrains.com/storage/products/company/brand/logos/jb_beam.svg)](https://www.jetbrains.com/?from=https://github.com/overtrue)

## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/overtrue/laravel-Votes/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/overtrue/laravel-Votes/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## PHP 扩展包开发

> 想知道如何从零开始构建 PHP 扩展包？
>
> 请关注我的实战课程，我会在此课程中分享一些扩展开发经验 —— [《PHP 扩展包实战教程 - 从入门到发布》](https://learnku.com/courses/creating-package)

## License

MIT
