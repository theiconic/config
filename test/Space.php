<?php

namespace TheIconic\Config;

use PHPUnit_Framework_TestCase;

/**
 * tests the Config helper
 *
 * @package Shared\Helper
 */
class SpaceTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        return $this->markTestSkipped('test needs to be adjusted to Config refactor');
    }

    /**
     * generates a config array for testing
     *
     * @return array
     */
    protected function getTestConfig()
    {
        return array(
            'key' => 'value for key',
            'path.to.key' => 'value for path to key',
            'intkey' => 0,
            'falsekey' => false,
            'nullkey' => null,
        );
    }

    /**
     * generates a mock config cache class
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockCache()
    {
        $cache = $this->getMock('TheIconic\Config\Cache', array('get'), array(array()));
        $cache->expects($this->once())
            ->method('get')
            ->will($this->returnValue($this->getTestConfig()));

        return $cache;
    }

    /**
     * generates a config instance
     * (we use getMock to bypass the singleton pattern)
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfig()
    {
        $config = $this->getMock('TheIconic\Config\Space', null, array(), '', false);
        $config->setCache($this->getMockCache());

        return $config;
    }

    /**
     * tests the Config::get() method
     */
    public function testGet()
    {
        $config = $this->getConfig();

        $expected = $this->getTestConfig();

        $this->assertSame($expected, $config->get());
        $this->assertSame($expected['key'], $config->get('key'));
        $this->assertSame($expected['path.to.key'], $config->get('path.to.key'));
        $this->assertSame($expected['intkey'], $config->get('intkey'));
        $this->assertFalse($config->get('falsekey'));
        $this->assertNull($config->get('unset.key'));
        $this->assertSame('default value', $config->get('unset.key', 'default value'));
        $this->assertSame('default value', $config->get('nullkey', 'default value'));
        $this->assertFalse($config->get('falsekey', 'default value'));
    }

}
