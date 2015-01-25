# Query Reactor

[![Build Status](https://api.travis-ci.org/Repo2/query-reactor.svg?branch=master)](https://travis-ci.org/Repo2/query-reactor)
[![Latest Stable Version](https://poser.pugx.org/repo2/query-reactor/v/stable.svg)](https://packagist.org/packages/repo2/query-reactor)
[![Total Downloads](https://poser.pugx.org/repo2/query-reactor/downloads.svg)](https://packagist.org/packages/repo2/query-reactor)
[![Latest Unstable Version](https://poser.pugx.org/repo2/query-reactor/v/unstable.svg)](https://packagist.org/packages/repo2/query-reactor)
[![License](https://poser.pugx.org/repo2/query-reactor/license.svg)](https://packagist.org/packages/repo2/query-reactor)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Repo2/query-reactor/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Repo2/query-reactor/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/Repo2/query-reactor/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Repo2/query-reactor/?branch=master)

Query Reactor is a non-blocking MySQL queries executor. The framework is simple and fast.
All you need is implement [```Query```](/src/Repo2/QueryReactor/Query.php) or use [```GenericQuery```](/src/Repo2/QueryReactor/Query/GenericQuery.php).

```php
use Psr\Log\NullLogger;
use Repo2\QueryBuilder;
use Repo2\QueryReactor;

$driver = new QueryReactor\Driver\Mysqli\MysqliDriver(new NullLogger());

$controller = new QueryReactor\Controller\PoolingController([
    'host' => 'localhost',
    'username' => 'root',
    'passwd' => '',
    'dbname' => 'test'
]);

$reactor = new QueryReactor\QueryReactor($driver, $controller);

$expression = QueryBuilder\select('user', ['id', 'name']);

$query = new QueryReactor\Query\GenericQuery(
    $expression,
    // on fulfill
    function (\Traversable $rows) {
        foreach ($rows as $row) {
            echo $row['id'], ' -> ', $row['name'], PHP_EOL;
        }
    },
    // on error
    function (\Exception $err) {
        throw $err;
    }
);

$reactor->execQuery($query);

$reactor->await();
```

**Table of contents**

1. [Installation](#installation)
1. [Components](#components)
   * [Driver](#driver)
   * [Controller](#controller)
   * [Query](#query)
1. [Sharding](#sharding)
1. [Restrictions](#restrictions)

## Installation
Install it with [Composer](https://getcomposer.org/):
```json
{
    "require": {
        "repo2/query-reactor": "*"
    }
}
```

## Components
The library requires [repo2/query-builder](https://github.com/Repo2/query-builder).

### Driver
The [```Driver```](/src/Repo2/QueryReactor/Driver.php) provides integration with low level DB API.
The API must support non-blocking queries execution.

> Currently the framework implements [```mysqli```](http://php.net/manual/en/book.mysqli.php) driver only.

```php
use Psr\Log\NullLogger;
use Repo2\QueryBuilder;
use Repo2\QueryReactor;

$driver = new QueryReactor\Driver\Mysqli\MysqliDriver(new NullLogger());

$expression = QueryBuilder\select('user', ['id', 'name']);

$link = $driver->connect(
    ['host' => 'localhost', 'dbname' => 'test'],
    'root',
    'some_secret_passwd'
);

$driver->query($link, $expression);

do {
    list($read, $error) = $driver->poll([$link]);
    foreach ($error as $link) {
        throw $driver->error($link);
    }
    foreach ($read as $link) {
        $result = $driver->getResult($link);
        if ($result instanceof \Traversable) {
            foreach ($result as $row) {
                echo $row['id'], ' -> ', $row['name'], PHP_EOL;
            }
            $driver->freeResult($result);
        }
    }
} while (!$read);
```

### Controller
The [```Controller```](/src/Repo2/QueryReactor/Controller.php) provides coordination between driver connection and query execution.

The framework includes [```PoolingController```](/src/Repo2/QueryReactor/Controller/PoolingController.php).
The controller provides basic logic for connection pooling and query queue.

```php
use Repo2\QueryReactor;

$controller = new QueryReactor\Controller\PoolingController([
    'host' => 'localhost',
    'username' => 'root',
    'passwd' => '',
    'dbname' => 'test',
    'max_connections' => 3 // max active connections (default: 10)
]);

do {
    $link = $controller->query($driver, $query);
    do {
        list($read, $error) = $driver->poll([$link]);
        // process $read and $error arrays
    } while (!$read && !$error);
    $query = $controller->next($driver, $link);
} while ($query);
```

### Query
The [```Query```](/src/Repo2/QueryReactor/Query.php) provides query definition and result processing.

#### getExpression
The method returns query expression.
```
function Query::getExpression()
```
> returns [```ExpressionInterface```](https://github.com/Repo2/query-builder/blob/master/src/Repo2/QueryBuilder/ExpressionInterface.php).

#### resolve
The method processes the query result and can create a subquery.
```
function Query::resolve(\Traversable $result)
```
> returns ```\Iterator```, ```Query``` or ```null```

```php
use Repo2\QueryBuilder;
use Repo2\QueryReactor;

$query = new QueryReactor\Query\GenericQuery(
    // select all users
    QueryBuilder\select('user', ['id', 'name']),
    // on fulfill
    function (\Traversable $result) {
        foreach ($result as $row) {
            // output a user
            echo $row['id'], ' -> ', $row['name'], PHP_EOL;
            yield new QueryReactor\Query\GenericQuery(
                // update account amount by random value
                QueryBuilder\update('account', ['amount' => mt_rand(10, 100)])
                ->where(
                    QueryBuilder\equal('user_id', $row['id'])
                )
            )
        }
    }
);

$reactor = new QueryReactor\QueryReactor($driver, $controller);
$reactor->execQuery($query);
$reactor->await();
```

#### reject
The method processes the query error.
```
function Query::reject(\Exception $error)
```
> returns void

## Sharding
The framework supports sharding by [```ShardingController```](/src/Repo2/QueryReactor/Sharding/ShardingController.php).

You should do 3 simple steps for getting started in the sharding:

1. implement [```ShardedQuery```](/src/Repo2/QueryReactor/Sharding/ShardedQuery.php)
    ```php
    use Repo2\QueryBuilder;
    use Repo2\QueryReactor;

    class UserQuery implements QueryReactor\Query, QueryReactor\Sharding\ShardedQuery
    {
        public static $table = 'user';

        public $id;

        public function resolve(\Traversable $result)
        {
            foreach ($result as $row) {
                echo $row['id'], ' -> ', $row['name'], PHP_EOL;
            }
        }

        public function reject(\Exception $err)
        {
            throw $err;
        }

        public function getExpression()
        {
            return QueryBuilder\select(self::$table, ['id', 'name'])
            ->where(QueryBuilder\equal('id', $this->id));
        }

        public function getDistributionName()
        {
            return self::$table;
        }

        public function getDistributionId()
        {
            return $this->id;
        }
    }
    ```
1. create own [```ShardingService```](/src/Repo2/QueryReactor/Sharding/ShardingService.php)
    ```php
    use Repo2\QueryReactor;

    class SimpleShardingService implements QueryReactor\Sharding\ShardingService
    {
        public static $primary = [
            'host' => 'localhost',
            'username' => 'root',
            'passwd' => '',
            'dbname' => 'test'
        ];

        public static $shards = [
            'user' => ['test1', 'test2', 'test3']
        ];

        public function selectGlobal()
        {
            return self::$primary;
        }

        public function selectShard($distributionName, $distributionValue)
        {
            $shards = self::$shards[$distributionName];
            $dbname = $shards[$distributionValue % count($shards)];
            return ['dbname' => $dbname] + self::$primary;
        }
    }
    ```
1. init the controller

    ```php
    use Repo2\QueryReactor;

    $controller = new QueryReactor\Sharding\ShardingController(
        new SimpleShardingService(),
        QueryReactor\Controller\PoolingController::class
    );

    $reactor = new QueryReactor\QueryReactor($driver, $controller);
    $reactor->execQuery(new UserQuery($userId));
    $reactor->await();
    ```

## Restrictions
The framework has some restrictions:
- No prepared statements.
- No "last insert id" in results.

Source: https://github.com/Repo2/query-reactor
