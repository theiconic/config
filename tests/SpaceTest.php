<?php

namespace TheIconic\Config;

use PHPUnit_Framework_TestCase;
use TheIconic\Config\Parser\Dummy;

/**
 * tests Config Space
 *
 * @package TheIconic\Config
 */
class SpaceTest extends PHPUnit_Framework_TestCase
{
    /**
     * test cached get
     */
    public function testGetCached()
    {
        $cache = $this->getMockBuilder(Cache::class)
            ->setConstructorArgs(['/tmp'])
            ->getMock();

        $cache->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $cache->expects($this->once())
            ->method('read')
            ->will($this->returnValue([
                'test' => [
                    'key' => 'abc',
                ],
            ]));

        $space = new Space('test', 'dev');
        $space->setCache($cache);

        $this->assertSame('abc', $space->get('test.key'));
    }

    /**
     * test uncached get
     */
    public function testGetUncached()
    {
        $cache = $this->getMockBuilder(Cache::class)
            ->setConstructorArgs(['/tmp'])
            ->getMock();

        $cache->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $parser = new Dummy();
        $parser->setContent([
            'dev' => [
                'test' => [
                    'key' => 'abc'
                ]
            ]
        ]);

        $space = new Space('test', 'dev');
        $space->setCache($cache);
        $space->setParser($parser);
        $space->addPath('/');

        $this->assertSame('abc', $space->get('test.key'));
    }

    /**
     * test flattened output
     */
    public function testFlatten()
    {
        $cache = $this->getMockBuilder(Cache::class)
            ->setConstructorArgs(['/tmp'])
            ->getMock();

        $cache->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $cache->expects($this->once())
            ->method('read')
            ->will($this->returnValue([
                'test' => [
                    'key1' => 'abc',
                ],
                'key2' => 'bcd',
            ]));

        $space = new Space('test', 'dev');
        $space->setCache($cache);

        $this->assertSame([
            'test.key1' => 'abc',
            'key2' => 'bcd',
        ], $space->flatten('test.key'));
    }

    /**
     * test uncached get with placeholders
     */
    public function testWithPlaceholder()
    {
        $cache = $this->getMockBuilder(Cache::class)
            ->setConstructorArgs(['/tmp'])
            ->getMock();

        $cache->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $parser = new Dummy();
        $parser->setContent([
            'dev' => [
                'test' => [
                    'key1' => '%value1%'
                ],
                'key2' => '%value2%',
            ]
        ]);

        $space = new Space('test', 'dev');
        $space->setCache($cache);
        $space->setParser($parser);
        $space->addPath('/');
        $space->addPlaceholder('%value1%', 'abc');
        $space->addPlaceholder('%value2%', 'bcd');

        $this->assertSame('abc', $space->get('test.key1'));
        $this->assertSame('bcd', $space->get('key2'));
    }
}
