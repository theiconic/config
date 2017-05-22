<?php

namespace TheIconic\Config;

use PHPUnit\Framework\TestCase;
use TheIconic\Config\Exception\PreconditionException;
use TheIconic\Config\Parser\Autodetect;
use TheIconic\Config\Parser\Dummy;

/**
 * tests Config Space
 *
 * @package TheIconic\Config
 */
class SpaceTest extends TestCase
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

        $space = new Space('test');
        $space->setCache($cache);
        $space->setSections(['all']);
        $space->addSection('dev');

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

        $space = new Space('test');
        $space->setCache($cache);
        $space->setSections(['all', 'dev']);
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

        $space = new Space('test');
        $space->setCache($cache);
        $space->setSections(['all', 'dev']);

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

        $space = new Space('test');
        $space->setCache($cache);
        $space->setParser($parser);
        $space->setSections(['all', 'dev']);
        $space->addPath('/');
        $space->setPlaceholders([
            '%value1%' => 'abc',
        ]);
        $space->addPlaceholder('%value2%', 'bcd');

        $this->assertSame([
            '%value1%' => 'abc',
            '%value2%' => 'bcd',
        ], $space->getPlaceholders());

        $this->assertSame('abc', $space->get('test.key1'));
        $this->assertSame('bcd', $space->get('key2'));
    }

    /**
     * test uncached get with placeholders
     */
    public function testMerging()
    {
        $cache = $this->getMockBuilder(Cache::class)
            ->setConstructorArgs(['/tmp'])
            ->getMock();

        $cache->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $parser = new Dummy();
        $parser->setContent([
            'all' => [
                'test' => [
                    'key1' => 'allValue1'
                ],
                'key2' => 'allValue2',
                'test3' => [
                    'abc'
                ],
            ],
            'dev' => [
                'test' => [
                    'key1' => 'devValue1',
                ],
                'test2' => [
                    'key3' => 123,
                ],
                'test3' => [
                    'abc'
                ],
            ]
        ]);

        $space = new Space('test');
        $space->setCache($cache);
        $space->setParser($parser);
        $space->setSections(['all', 'dev']);
        $space->addPath('/');

        $this->assertSame('devValue1', $space->get('test.key1'));
        $this->assertSame('allValue2', $space->get('key2'));
        $this->assertSame(123, $space->get('test2.key3'));
        $this->assertSame([
            'abc',
            'abc',
        ], $space->get('test3'));
    }
    
    public function testMissingSectionsThrowsException()
    {
        $cache = $this->getMockBuilder(Cache::class)
            ->setConstructorArgs(['/tmp'])
            ->getMock();

        $cache->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $parser = new Dummy();
        $parser->setContent([
            'abc' => 'abc',
        ]);
        
        $this->expectException(PreconditionException::class);

        $space = new Space('test');
        $space->setCache($cache);
        $space->setParser($parser);
        $space->get('abc');
    }

    public function testGetParserReturnsAutodetectParser()
    {
        $space = new Space('test');
        $this->assertInstanceOf(Autodetect::class, $space->getParser());
    }

    public function testGetReturnsDefault()
    {
        $cache = $this->getMockBuilder(Cache::class)
            ->setConstructorArgs(['/tmp'])
            ->getMock();

        $cache->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $parser = new Dummy();
        $parser->setContent([]);

        $space = new Space('test');
        $space->setCache($cache);
        $space->setParser($parser);
        $space->setSections(['all']);
        $this->assertSame('abcd', $space->get('non_existing_key', 'abcd'));
    }

    public function testPathHandling()
    {
        $cache = $this->getMockBuilder(Cache::class)
            ->setConstructorArgs(['/tmp'])
            ->getMock();

        $cache->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $parser = $this->getMockBuilder(Dummy::class)
            ->setMethods(['parse'])
            ->getMock();
        
        $parser->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive(['/tmp/config/a.ini'], ['/tmp/config/b.ini'], ['/tmp/config/c.ini'])
            ->willReturn([]);
        
        $space = $this->getMockBuilder(Space::class)
            ->setConstructorArgs(['test'])
            ->setMethods(['getReadableSources', 'getTimestamp'])
            ->getMock();

        $space->expects($this->once())
            ->method('getReadableSources')
            ->willReturnCallback(function () use ($space) {
                return $space->getPaths();
            });

        $space->setPaths(['/tmp/config/a.ini', '/tmp/config/b.ini', '/tmp/config/c.ini']);
        $space->setParser($parser);
        $space->setSections(['all']);
        $space->setCache($cache);
        
        $space->get('abc');
    }
}
