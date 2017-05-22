<?php

namespace TheIconic\Config\Parser;

use PHPUnit_Framework_TestCase;
use TheIconic\Config\Exception\ParserException;

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

    public function testUnsupported()
    {
        $parser = new Autodetect();
        $parser->setParsers([
            'ini' => new Ini(),
            'php' => new Php(),
        ]);

        $this->setExpectedException(ParserException::class);
        $parser->parse('config.json');
    }

    public function testUnconfigured()
    {
        $parser = new Autodetect();
        
        $this->assertEquals([
            'php' => new Php(),
            'json' => new Json(),
            'ini' => new Ini(),
        ], $parser->getParsers());
    }
}
