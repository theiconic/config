<?php

namespace TheIconic\Config\Parser;

use PHPUnit_Framework_TestCase;

/**
 * test Autodetect parser
 *
 * @package TheIconic\Config\Parser
 */
class AutodetectTest extends PHPUnit_Framework_TestCase
{
    /**
     * test parsing of ini file
     */
    public function testParse()
    {
        $iniParser = $this->getMockBuilder(Ini::class)
            ->getMock();

        $iniParser->expects($this->once())
            ->method('parse')
            ->will($this->returnValue('ini'));

        $phpParser = $this->getMockBuilder(Php::class)
            ->getMock();

        $phpParser->expects($this->once())
            ->method('parse')
            ->will($this->returnValue('php'));

        $parser = new Autodetect();
        $parser->setParsers([
            'ini' => $iniParser,
            'php' => $phpParser,
        ]);

        $this->assertSame('ini', $parser->parse('config.ini'));
        $this->assertSame('php', $parser->parse('config.php'));
    }
}