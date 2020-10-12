<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii Caching Library</h1>
    <br>
</p>

This library provides a wrapper around [PSR-16] compatible caching libraries adding more features.
It is used in [Yii Framework] but is usable separately.

[PSR-16]: https://www.php-fig.org/psr/psr-16/
[Yii Framework]: https://www.yiiframework.com/

[![Latest Stable Version](https://poser.pugx.org/yiisoft/cache/v/stable.png)](https://packagist.org/packages/yiisoft/cache)
[![Total Downloads](https://poser.pugx.org/yiisoft/cache/downloads.png)](https://packagist.org/packages/yiisoft/cache)
[![Build Status](https://travis-ci.com/yiisoft/cache.svg?branch=master)](https://travis-ci.com/yiisoft/cache)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/cache/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/cache/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/cache/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/cache/?branch=master)

## Features

- Built on top of PSR-16, could be used as PSR-16 cache or use any PSR-16 cache as backend.
- Provides multiple cache backends: APC, PHP array, files, memcached, WinCache.
- Customizable way of serializing data. Out of the box PHP, JSON, Igbinary and custom callbacks are supported.
- Ability to set default TTL and key prefix per cache instance.
- Easy to implement your own cache backends extending from `SimpleCache`.
- Adds cache invalidation dependencies on top of PSR-16. Out of the box supports invalidation by tag and invalidation by 
  file modification time.
- Adds support for `add()` and `addMultiple()` operations additionally to PSR-16.
- Adds handy `getOrSet()` method additionally to PSR-16.

## Configuration

There are two ways to get cache instance. If you need plain PSR-16 instance, you can simply create it:

```php
$cache = new ApcuCache();
```

If you need additional features such as invalidation dependencies, `add()`, `addMultiple()` or `getOrSet()` you should
wrap PSR-16 cache instance with `Cache`:

```php
$cache = new Cache(new ApcuCache());
```

In order to change default serializer you can use `setSerializer()` method:

```php
$cache = new WinCache();
$cache->setSerializer(new JsonSerializer());
```

Default TTL could be set via `setDefaultTtl()`:

```php
$cache = new ArrayCache();
$cache->setDefaultTtl(60 * 60); // 1 hour
```

In order to set key prefix for a cache instance, use `setKeyPrefix()` method:

```php
$cache = new Memcached();
$cache->setKeyPrefix('myapp');
```

## Usage

Typical cache usage is the following:

```php
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
```

To work with values in a more efficient manner, batch operations should be used:

- `getMultiple()`
- `setMultiple()`
- `deleteMultiple()`

When using extended cache i.e. PSR-16 cache wrapped with `\Yiisoft\Cache\Cache`, you can use alternative syntax that
is less repetitive:

```php
$parameters = ['user_id' => 42];
$data = $cache->getOrSet($key, function () use ($parameters) {
    return $this->calculateSomething($parameters);
}, 3600);
```

Additionally, `add()` and `addMultiple()` are avaialble. These methods work like `set()` and `setMultiple()` except
they store cache only if there is no existing value.

### Invalidation dependencies

When using extended cache i.e. PSR-16 cache wrapped with `\Yiisoft\Cache\Cache`, additionally to TTL for `set()`,
`setMultiple()`, `add()`, `addMultiple()` or `getOrSet()` methods you can specify a dependency that may trigger cache
invalidation. Below is an example using tag dependency:

```php
// set multiple cache values marking both with a tag
$cache->set('item_42_price', 13, null, new TagDependency('item_42'));
$cache->set('item_42_total', 26, null, new TagDependency('item_42'));

// trigger invalidation by tag
TagDependency::invalidate($cache, 'item_42');
```

Out of there is file dependency that invalidates cache based on file modification time and callback dependency that
invalidates cache when callback result changes.

In order to implement your own dependency extend from `Yiisoft\Cache\Dependency\Dependency`.

You may combine multiple dependencies using `AnyDependency` or `AllDependencies`. 


## Implementing your own cache backend

There are two ways to implement cache backend. You can start from scratch by implementing `\Psr\SimpleCache\CacheInterface`
or you can inherit from `\Yiisoft\Cache\SimpleCache`. In the latter case you have to implement the following methods:

- `hasValue()` - check if value with a key exists in cache.
- `getValue()` - retrieve the value with a key (if any) from cache
- `setValue()` - store the value with a key into cache
- `deleteValue()` - delete the value with the specified key from cache
- `clear()` - delete all values from cache

Additionally, you may override the following methods in case backend supports getting any/or setting multiple keys
at once:

- `getValues()` - retrieve multiple values from cache
- `setValues()` - store multiple values into cache
- `deleteValues()` - delete multiple values from cache
