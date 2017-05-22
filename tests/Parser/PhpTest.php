<?php

namespace TheIconic\Config\Parser;

use PHPUnit_Framework_TestCase;
use org\bovigo\vfs\vfsStream;
use TheIconic\Config\Exception\ParserException;

/**
 * test Php parser
 *
 * @package TheIconic\Config\Parser
 */
class PhpTest extends PHPUnit_Framework_TestCase
{
    /**
     * test parse()
     */
    public function testParse()
    {
        $content = <<<EOF
<?php
return [
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
];
EOF;

        $root = vfsStream::setup('phptest');
        vfsStream::newFile('config.php')
            ->at($root)
            ->withContent($content);

        $parser = new Php();

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
        ], $parser->parse(vfsStream::url('phptest/config.php')));
    }

    /**
     * test parse()
     */
    public function testParseInvalidFile()
    {
        $content = <<<EOF
<?php
abcdef
call_something_that_doesn't_exist();
return blabla();
EOF;

        $root = vfsStream::setup('phptest');
        vfsStream::newFile('config.php')
            ->at($root)
            ->withContent($content);

        $parser = new Php();

        $this->setExpectedException(ParserException::class);

        $parser->parse(vfsStream::url('phptest/config.php'));
    }
}
