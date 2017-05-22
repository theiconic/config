<?php

namespace TheIconic\Config;

use PHPUnit\Framework\TestCase;
use TheIconic\Config\Exception\PreconditionException;

class FactoryTest extends TestCase
{
    public function testGetSpace()
    {
        $factory = new Factory();
        $factory->setCachePath('/tmp');

        $cache = $factory->getCache();

        $space1 = $factory->getSpace('test1');
        $space2 = $factory->getSpace('test2');

        $this->assertSame('/tmp', $factory->getCachePath());
        $this->assertInstanceOf(Cache::class, $cache);
        $this->assertSame('/tmp', $cache->getBasePath());

        $this->assertInstanceOf(Space::class, $space1);
        $this->assertInstanceOf(Space::class, $space2);

        $this->assertSame($space1, $factory->getSpace('test1'));
        $this->assertSame($space2, $factory->getSpace('test2'));

        $this->assertSame($cache, $factory->getSpace('test1')->getCache());
        $this->assertSame($cache, $factory->getSpace('test2')->getCache());

        $factory->setCache($cache);

        $this->assertSame($cache, $factory->getCache());
    }

    public function testUnconfigured()
    {
        $factory = new Factory();

        $this->setExpectedException(PreconditionException::class);

        $factory->getSpace('test');
    }

    public function testGetInstance()
    {
        $factory = Factory::getInstance();

        $this->assertInstanceOf(Factory::class, $factory);
    }
}
