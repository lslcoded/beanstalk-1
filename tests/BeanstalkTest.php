<?php

namespace Phlib\Beanstalk\Tests;

use Phlib\Beanstalk\Beanstalk;
use Phlib\Beanstalk\JobPackager\Raw;
use Phlib\Beanstalk\Socket;

class BeanstalkTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Socket
     */
    protected $socket;

    /**
     * @var Beanstalk
     */
    protected $beanstalk;

    public function setUp()
    {
        $this->socket = $this->getMockBuilder('\Phlib\Beanstalk\Socket')
            ->disableOriginalConstructor()
            ->getMock();
        $this->beanstalk = new Beanstalk('host');
        $this->beanstalk->setSocket($this->socket);
        parent::setUp();
    }

    public function testImplementsInterface()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\BeanstalkInterface', $this->beanstalk);
    }

    public function testSocketIsSetCorrectly()
    {
        $this->assertEquals($this->socket, $this->beanstalk->getSocket());
    }

    public function testDefaultSocketImplementation()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\Socket', $this->beanstalk->getSocket());
    }

    public function testDefaultJobPackager()
    {
        $this->assertInstanceOf('\Phlib\Beanstalk\JobPackager\Json', $this->beanstalk->getJobPackager());
    }

    public function testCanSetJobPackager()
    {
        $packager = new Raw();
        $this->beanstalk->setJobPackager($packager);
        $this->assertEquals($packager, $this->beanstalk->getJobPackager());
    }

    public function testPut()
    {
        $this->socket->expects($this->atLeastOnce())
            ->method('read')
            ->willReturn('INSERTED 123');
        $this->beanstalk->put('foo-bar');
    }

    public function testReserve()
    {
        $this->socket->expects($this->atLeastOnce())
            ->method('read')
            ->willReturn('RESERVED 123 456');
        $this->beanstalk->reserve();
    }

    public function testReserveDecodesData()
    {
        $expectedData = ['foo' => 'bar' , 'bar' => 'baz'];
        $this->socket->expects($this->atLeastOnce())
            ->method('read')
            ->will($this->onConsecutiveCalls('RESERVED 123 456', json_encode($expectedData) . "\r\n"));
        $jobData = $this->beanstalk->reserve();
        $this->assertEquals($expectedData, $jobData['body']);
    }

    public function testDelete()
    {
        $id = 234;
        $this->execute("delete $id", 'DELETED', 'delete', [$id]);
    }

    public function testRelease()
    {
        $id = 234;
        $this->execute("release $id", 'RELEASED', 'release', [$id]);
    }

    public function testUseTube()
    {
        $tube = 'test-tube';
        $this->execute("use $tube", 'USING', 'useTube', [$tube]);
    }

    public function testBury()
    {
        $id = 534;
        $this->execute("bury $id", 'BURIED', 'bury', [$id]);
    }

    public function testTouch()
    {
        $id = 567;
        $this->execute("touch $id", 'TOUCHED', 'touch', [$id]);
    }

    public function testWatch()
    {
        $tube = 'test-tube';
        $this->execute("watch $tube", "WATCHING $tube", 'watch', [$tube]);
    }

    public function testWatchForExistingWatchedTube()
    {
        $tube = 'test-tube';
        $this->execute("watch $tube", "WATCHING 123", 'watch', [$tube]);
        $this->beanstalk->watch($tube);
    }

    public function testIgnore()
    {
        $tube = 'test-tube';
        $this->socket->expects($this->any())
            ->method('read')
            ->willReturn('WATCHING 123');
        $this->beanstalk->watch($tube);
        $this->execute("ignore $tube", 'WATCHING 123', 'ignore', [$tube]);
    }

    public function testIgnoreDoesNothingWhenNotWatching()
    {
        $tube = 'test-tube';
        $this->socket->expects($this->never())
            ->method('write');
        $this->beanstalk->ignore($tube);
    }

    public function testPeek()
    {
        $id = 245;
        $this->execute("peek $id", ["FOUND $id 678", '{"foo":"bar","bar":"baz"}'], 'peek', [$id]);
    }

    public function testPeekReady()
    {
        $this->execute("peek-ready", ["FOUND 234 678", '{"foo":"bar","bar":"baz"}'], 'peekReady', []);
    }

    public function testPeekDelayed()
    {
        $this->execute("peek-delayed", ["FOUND 234 678", '{"foo":"bar","bar":"baz"}'], 'peekDelayed', []);
    }

    public function testPeekBuried()
    {
        $this->execute("peek-buried", ["FOUND 234 678", '{"foo":"bar","bar":"baz"}'], 'peekBuried', []);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\NotFoundException
     */
    public function testPeekNotFound()
    {
        $id = 245;
        $this->execute("peek $id", 'NOT_FOUND', 'peek', [$id]);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\NotFoundException
     */
    public function testPeekReadyNotFound()
    {
        $this->execute("peek-ready", 'NOT_FOUND', 'peekReady', []);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\NotFoundException
     */
    public function testPeekDelayedNotFound()
    {
        $this->execute("peek-delayed", 'NOT_FOUND', 'peekDelayed', []);
    }

    /**
     * @expectedException \Phlib\Beanstalk\Exception\NotFoundException
     */
    public function testPeekBuriedNotFound()
    {
        $this->execute("peek-buried", 'NOT_FOUND', 'peekBuried', []);
    }

    public function testKick()
    {
        $bound = 123;
        $this->execute("kick $bound", "KICKED $bound", 'kick', [$bound]);
    }

    public function testKickEmpty()
    {
        $bound = 1;
        $quantity = $this->execute("kick $bound", "KICKED 0", 'kick', [$bound]);
        $this->assertEquals(0, $quantity);
    }

    public function testDefaultListOfTubesWatched()
    {
        $expected = ['default'];
        $this->assertEquals($expected, $this->beanstalk->listTubesWatched());
    }

    public function testDefaultTubeUsed()
    {
        $this->assertEquals('default', $this->beanstalk->listTubeUsed());
    }

    protected function execute($command, $response, $method, array $arguments)
    {
        $this->socket->expects($this->once())
            ->method('write')
            ->with($this->stringContains($command));
        if (is_array($response)) {
            $thisReturn = call_user_func_array([$this, 'onConsecutiveCalls'], $response);
            $this->socket->expects($this->any())
                ->method('read')
                ->will($thisReturn);
        } else {
            $this->socket->expects($this->any())
                ->method('read')
                ->willReturn($response);
        }
        return call_user_func_array([$this->beanstalk, $method], $arguments);
    }
}
