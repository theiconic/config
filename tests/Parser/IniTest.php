<?php

namespace TheIconic\Config\Parser;

use PHPUnit_Framework_TestCase;
use org\bovigo\vfs\vfsStream;
use TheIconic\Config\Exception\ParserException;

/**
 * test Ini parser
 *
 * @package TheIconic\Config\Parser
 */
class IniTest extends PHPUnit_Framework_TestCase
{
    /**
     * test parse()
     */
    public function testParse()
    {
        $content = <<<EOF
[all]
test.key1 = "abc"

[dev]
key2 = "def"

[live]
test.live.key3 = 'true'
EOF;

        $root = vfsStream::setup('initest');
        vfsStream::newFile('config.ini')
            ->at($root)
            ->withContent($content);

        $parser = new Ini();

        $this->assertSame([
            'all' => [
                'test' => [
                    'key1' => 'abc',
                ],
            ],
            'dev' => [
                'key2' => 'def',
            ],
            'live' => [
                'test' => [
                    'live' => [
                        'key3' => true
                    ]
                ]
            ]
        ], $parser->parse(vfsStream::url('initest/config.ini')));
    }

    /**
     * test parse()
     */
    public function testParseInvalidFile()
    {
        $content = <<<EOF
abcdefg=asdf=asdf=
asdfasdf
EOF;

        $root = vfsStream::setup('initest');
        vfsStream::newFile('config.ini')
            ->at($root)
            ->withContent($content);

        $parser = new Ini();
        
        $this->setExpectedException(ParserException::class);

        $parser->parse(vfsStream::url('initest/config.ini'));
    }
}