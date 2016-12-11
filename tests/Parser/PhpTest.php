<?php

namespace TheIconic\Config\Parser;

use PHPUnit_Framework_TestCase;
use org\bovigo\vfs\vfsStream;

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

        $root = vfsStream::setup('initest');
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
        ], $parser->parse(vfsStream::url('initest/config.php')));
    }
}