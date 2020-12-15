<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii Caching Library</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/cache/v/stable.png)](https://packagist.org/packages/yiisoft/cache)
[![Total Downloads](https://poser.pugx.org/yiisoft/cache/downloads.png)](https://packagist.org/packages/yiisoft/cache)
[![Build status](https://github.com/yiisoft/cache/workflows/build/badge.svg)](https://github.com/yiisoft/cache/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/cache/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/cache/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/cache/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/cache/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fcache%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/cache/master)
[![static analysis](https://github.com/yiisoft/cache/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/cache/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/cache/coverage.svg)](https://shepherd.dev/github/yiisoft/cache)

This library is a wrapper around [PSR-16](https://www.php-fig.org/psr/psr-16/) compatible caching libraries
providing own features. It is used in [Yii Framework](https://www.yiiframework.com/) but is usable separately.

## Features

- Built on top of PSR-16, it can use any PSR-16 cache as a handler.
- Ability to set default TTL and key prefix per cache instance.
- Provides a built-in behavior to cache stampede prevention.
- Adds cache invalidation dependencies on top of PSR-16.
  
## Installation

The package could be installed with composer:

```
composer install yiisoft/cache
```

## Configuration

There are two ways to get cache instance. If you need PSR-16 instance, you can simply create it:

```php
$arrayCache = new \Yiisoft\Cache\ArrayCache();
```

If you need a simpler yet more powerful way to cache values based on recomputation callbacks using just
two methods `getOrSet()` and `remove()`. Plus additional features such as invalidation dependencies and
"Probably early expiration", you should wrap PSR-16 cache instance with `\Yiisoft\Cache\Cache`:

```php
$cache = new \Yiisoft\Cache\Cache($arrayCache);
```

Set a default TTL:

```php
$cache = new \Yiisoft\Cache\Cache($arrayCache, 60 * 60); // 1 hour
```

Set a key prefix:

```php
$cache = new \Yiisoft\Cache\Cache($arrayCache, null, 'myapp');
```

## General usage

Typical PSR-16 cache usage is the following:

```php
$cache = new \Yiisoft\Cache\ArrayCache();
$parameters = ['user_id' => 42];
$key = 'demo';

// try retrieving $data from cache
$data = $cache->get($key);
if ($data === null) {
    // $data is not found in cache, calculate it from scratch
    $data = calculateData($parameters);
    
    // store $data in cache for an hour so that it can be retrieved next time
    $cache->set($key, $data, 3600);
}

// $data is available here
```

In order to delete value you can use:

```php
$cache->delete($key);
// Or all cache
$cache->clear();
```

To work with values in a more efficient manner, batch operations should be used:

- `getMultiple()`
- `setMultiple()`
- `deleteMultiple()`

When using extended cache i.e. PSR-16 cache wrapped with `\Yiisoft\Cache\Cache`, you can use alternative syntax that
is less repetitive:

```php
$cache = new \Yiisoft\Cache\Cache(new \Yiisoft\Cache\ArrayCache());
$key = ['top-products', $count = 10];

$data = $cache->getOrSet($key, function (\Psr\SimpleCache\CacheInterface $cache) use ($count) {
    return getTopProductsFromDatabase($count);
}, 3600);
```

In order to delete value you can use:

```php
$cache->remove($key);
```

### Invalidation dependencies

When using `\Yiisoft\Cache\Cache`, additionally to TTL for `getOrSet()` method you can specify a dependency
that may trigger cache invalidation. Below is an example using tag dependency:

```php
/**
 * @var callable $callable
 * @var \Yiisoft\Cache\CacheInterface $cache
 */

use Yiisoft\Cache\Dependency\TagDependency;

// set multiple cache values marking both with a tag
$cache->getOrSet('item_42_price', $callable, null, new TagDependency('item_42'));
$cache->set('item_42_total', $callable, 3600, new TagDependency('item_42'));

// trigger invalidation by tag
TagDependency::invalidate($cache, 'item_42');
```

Out of there is `Yiisoft\Cache\Dependency\FileDependency` that invalidates cache based on file modification time
and `Yiisoft\Cache\Dependency\CallbackDependency` that invalidates cache when callback result changes.

In order to implement your own dependency extend from `Yiisoft\Cache\Dependency\Dependency`.

You may combine multiple dependencies using `Yiisoft\Cache\Dependency\AnyDependency`
or `Yiisoft\Cache\Dependency\AllDependencies`. 


### Probably early expiration

The `\Yiisoft\Cache\Cache` uses a built-in "Probably early expiration" algorithm that prevents cache stampede.
This algorithm randomly fakes a cache miss for one user while others are still served the cached value.
You can control its behavior with the fifth optional parameter of `getOrSet()`, which is a float value called `$beta`.
By default, beta is `1.0`, which is sufficient in most cases. The higher values mean earlier recompute.

```php
/**
 * @var mixed $key
 * @var callable $callable
 * @var \DateInterval $ttl
 * @var \Yiisoft\Cache\CacheInterface $cache
 * @var \Yiisoft\Cache\Dependency\Dependency $dependency
 */

$beta = 2.0;
$cache->getOrSet($key, $callable, $ttl, $dependency, $beta);
```

### Cache handlers

> Under the handler refers to the implementations of [PSR-16](https://www.php-fig.org/psr/psr-16/).

This package contains two handlers:

- `Yiisoft\Cache\ArrayCache` - provides caching for the current request only by storing the values in an array.
- `Yiisoft\Cache\NullCache` - does not cache anything reporting success for all methods calls.

Extra cache handlers are implemented as separate packages:

- [APCu](https://github.com/yiisoft/cache-apcu)
- [Database](https://github.com/yiisoft/cache-db)
- [File](https://github.com/yiisoft/cache-file)
- [Memcached](https://github.com/yiisoft/cache-memcached)
- [Wincache](https://github.com/yiisoft/cache-wincache)

### Unit testing

```php
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```php
./vendor/bin/infection
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```php
./vendor/bin/psalm
```

### Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

### Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3ru)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)

## License

The Yii Caching Library is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).
