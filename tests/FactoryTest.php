<?php

namespace Phlib\Beanstalk\Tests;

use Phlib\Beanstalk\Connection;
use Phlib\Beanstalk\Connection\ConnectionInterface;
use Phlib\Beanstalk\Exception\InvalidArgumentException;
use Phlib\Beanstalk\Factory;
use Phlib\Beanstalk\Pool;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
    }
    
    public function tearDown()
    {
        parent::tearDown(); // TODO: Change the autogenerated stub
    }

    public function testCreate()
    {
        $this->assertInstanceOf(ConnectionInterface::class, Factory::create('localhost'));
    }

    /**
     * @dataProvider createFromArrayDataProvider
     */
    public function testCreateFromArray($expectedClass, $config)
    {
        $this->assertInstanceOf(ConnectionInterface::class, Factory::create('localhost'));
    }

    public function createFromArrayDataProvider()
    {
        $connectionClass = Connection::class;
        $poolClass       = Pool::class;
        $defaultHost     = ['host' => 'localhost'];

        return [
            [$connectionClass, $defaultHost],
            [$connectionClass, ['host' => 'localhost', 'port' => 123456]],
            [$connectionClass, ['server' => $defaultHost]],
            [$poolClass, ['servers' => [$defaultHost, $defaultHost]]]
        ];
    }

    /**
     * @param string $strategyClass
     * @dataProvider creatingPoolUsesStrategyDataProvider
     */
    public function testCreatingPoolUsesStrategy($strategyClass)
    {
        $hostConfig = ['host' => 'localhost'];
        $poolConfig = [
            'servers' => [$hostConfig, $hostConfig],
            'strategyClass' => $strategyClass
        ];
        $pool = Factory::createFromArray($poolConfig);
        /* @var $pool Pool */

        $collection = $pool->getCollection();
        /* @var $collection Pool\Collection */

        $this->assertInstanceOf($strategyClass, $collection->getSelectionStrategy());
    }

    public function creatingPoolUsesStrategyDataProvider()
    {
        return [
            [Pool\RoundRobinStrategy::class],
            [Pool\RandomStrategy::class]
        ];
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\InvalidArgumentException
     */
    public function testCreatingPoolFailsWithInvalidStrategyClass()
    {
        $hostConfig = ['host' => 'localhost'];
        $poolConfig = [
            'servers' => [$hostConfig, $hostConfig],
            'strategyClass' => '\Some\RandomClass\ThatDoesnt\Exist'
        ];
        Factory::createFromArray($poolConfig);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\InvalidArgumentException
     */
    public function testCreateFromArrayFailsWhenEmpty()
    {
        Factory::createFromArray([]);
    }

    public function testCreateConnections()
    {
        $result = true;
        $config = ['host' => 'locahost'];

        $connections = Factory::createConnections([$config, $config, $config]);
        foreach ($connections as $connection) {
            $result = $result && $connection instanceof Connection;
        }

        $this->assertTrue($result);
    }
}