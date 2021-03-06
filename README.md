# phlib/beanstalk

[![Build Status](https://img.shields.io/travis/phlib/beanstalk/master.svg)](https://travis-ci.org/phlib/beanstalk)
[![Latest Stable Version](https://img.shields.io/packagist/v/phlib/beanstalk.svg)](https://packagist.org/packages/phlib/beanstalk)
[![Total Downloads](https://img.shields.io/packagist/dt/phlib/beanstalk.svg)](https://packagist.org/packages/phlib/beanstalk)

Beanstalkd library implementation.

## Install

Via Composer

``` bash
$ composer require phlib/beanstalk
```

## Basic Usage

``` php
<?php
use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\Connection\Socket;

// producer
$beanstalk = new Connection(new Socket('127.0.0.1'));
$beanstalk->useTube('my-tube');
$beanstalk->put(array('my' => 'jobData'));
```

``` php
<?php
use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\Connection\Socket;

// consumer
$beanstalk = new Connection(new Socket('127.0.0.1'));
$beanstalk->watch('my-tube')
    ->ignore('default');
$job = $beanstalk->reserve();
$myJobData = $job['body'];
$beanstalk->delete($job['id']);
```

## Configuration

|Name|Type|Required|Default|Description|
|----|----|--------|-------|-----------|
|`host`|*String*|Yes| |Hostname or IP address.|
|`port`|*Integer*|No|`11300`|Beanstalk's port.|
|`options`|*Array*|No|`<empty>`|Connection options for Beanstalk.|

### Options

|Name|Type|Default|Description|
|----|----|-------|-----------|
|`timeout`|*Integer*|`60`|The connection timeout.|

## Factory
The factory allows for easy setup of the objects. This especially useful when creating a pool of beanstalk servers. The
following example lists the various ways it can be used. The configuration examples in the command line section are 
created using the factory.

```php
use Phlib\Beanstalk\Factory;

$beanstalk = Factory::create('localhost');

$beanstalk = Factory::createFromArray(['host' => 'localhost']);

$beanstalk = Factory::createFromArray([
    'server' => ['host' => 'localhost']
]);

$beanstalk = Factory::createFromArray([
    'servers' => [
        ['host' => '10.0.0.1'],
        ['host' => '10.0.0.2'],
        ['host' => '10.0.0.3']
    ]
]);

```

## Pool
The pool allows for work to be pushed to and retrieved from multiple servers. The pool implements the connection 
interface.

```php
use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\Connection\Socket;
use Phlib\Beanstalk\Pool;
use Phlib\Beanstalk\Pool\Collection;
use Phlib\Beanstalk\Pool\RoundRobinStrategy;

$servers = [
    new Connection(new Socket('10.0.0.1')),
    new Connection(new Socket('10.0.0.2')),
    new Connection(new Socket('10.0.0.3')),
    new Connection(new Socket('10.0.0.4'))
];
$strategy = new RoundRobinStrategy
$pool = new Pool(new Collection($servers, $strategy, ['retry_delay' => '120']));

$pool->useTube('my-tube');
$pool->put(array('my' => 'jobData1')); // <- sent to server 1
$pool->put(array('my' => 'jobData2')); // <- sent to server 2
$pool->put(array('my' => 'jobData3')); // <- sent to server 3
```

## Command Line Script

```bash
./vendor/bin/beanstalk
```

Running the script will provide you with a list of options. Most are self explanatory. By default no configuration is 
required, the script will default to localhost.

### Command Line Configuration

There are 2 ways of specifying a configuration.

1. Create a file called beanstalk-config.php either in ```/app/root``` or ```/app/root/config```.
2. Create a file with a name of your choosing and specify it using the command option ```-c /path/to/my/config.php```

The file must return an array containing the beanstalk configuration. This configuration will be passed to the Factory
to create an instance.

```php
return [
    'host' => '10.0.0.1',
    'port' => 11300
];
```

```php
return [
    'server' => [
        'host' => '10.0.0.1',
        'port' => 11300
    ]
];
```

```php
// pool configuration
return [
    'servers' => [
        [
            'host' => '10.0.0.1',
            'port' => 11300
        ],
        [
            'host' => '10.0.0.2',
            'port' => 11300
        ],
        [
            'host' => '10.0.0.3',
            'port' => 11300,
            'enabled' => false
        ]
    ]
];
```

```php
require_once 'my/app/bootstrap.php';

$app = new MyApp();
return $app['config']['beanstalk'];

```
