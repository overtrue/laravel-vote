Laravel Vote
---

❤️ User Vote feature for Laravel Application.

![CI](https://github.com/overtrue/laravel-Vote/workflows/CI/badge.svg)


## Installing

```shell
$ composer require overtrue/laravel-Vote -vvv
```

### Configuration

This step is optional

```php
$ php artisan vendor:publish --provider="Overtrue\\LaravelVote\\VoteServiceProvider" --tag=config
```

### Migrations

This step is also optional, if you want to custom Votes table, you can publish the migration files:

```php
$ php artisan vendor:publish --provider="Overtrue\\LaravelVote\\VoteServiceProvider" --tag=migrations
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
use Overtrue\LaravelVote\Traits\Voteable;

class Post extends Model
{
    use Voteable;

    <...>
}
```

### API

```php
$user = User::find(1);
$post = Post::find(2);

$user->Vote($post);
$user->unVote($post);
$user->toggleVote($post);
$user->getVoteItems(Post::class)

$user->hasVoted($post); 
$post->isVotedBy($user); 
```

#### Get object Voters:

```php
foreach($post->Voters as $user) {
    // echo $user->name;
}
```

#### Get Vote Model from User.
Used Voter Trait Model can easy to get Voteable Models to do what you want.
*note: this method will return a `Illuminate\Database\Eloquent\Builder` *
```php
$user->getVoteItems(Post::class);

// Do more
$favortePosts = $user->getVoteItems(Post::class)->get();
$favortePosts = $user->getVoteItems(Post::class)->paginate();
$favortePosts = $user->getVoteItems(Post::class)->where('title', 'Laravel-Vote')->get();
```

### Aggregations

```php
// all
$user->Votes()->count(); 

// with type
$user->Votes()->withType(Post::class)->count(); 

// Voters count
$post->Voters()->count();
```

List with `*_count` attribute:

```php
$users = User::withCount('Votes')->get();

foreach($users as $user) {
    echo $user->Votes_count;
}
```


### N+1 issue

To avoid the N+1 issue, you can use eager loading to reduce this operation to just 2 queries. When querying, you may specify which relationships should be eager loaded using the `with` method:

```php
// Voter
$users = App\User::with('Votes')->get();

foreach($users as $user) {
    $user->hasVoted($post);
}

// Voteable
$posts = App\Post::with('Votes')->get();
// or 
$posts = App\Post::with('Voters')->get();

foreach($posts as $post) {
    $post->isVotedBy($user);
}
```


### Events

| **Event** | **Description** |
| --- | --- |
|  `Overtrue\LaravelVote\Events\Voted` | Triggered when the relationship is created. |
|  `Overtrue\LaravelVote\Events\UnVoted` | Triggered when the relationship is deleted. |

## Related packages

- Follow: [overtrue/laravel-follow](https://github.com/overtrue/laravel-follow)
- Like: [overtrue/laravel-like](https://github.com/overtrue/laravel-like)
- Vote: [overtrue/laravel-Vote](https://github.com/overtrue/laravel-Vote)
- Subscribe: [overtrue/laravel-subscribe](https://github.com/overtrue/laravel-subscribe)
- Vote: overtrue/laravel-vote (working in progress)
- Bookmark: overtrue/laravel-bookmark (working in progress)


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
