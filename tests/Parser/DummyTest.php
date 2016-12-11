<?php

namespace TheIconic\Config\Parser;

use PHPUnit_Framework_TestCase;

/**
 * test Dummy parser
 *
 * @package TheIconic\Config\Parser
 */
class DummyTest extends PHPUnit_Framework_TestCase
{
    /**
     * test parse()
     */
    public function testParse()
    {
        $content = [
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

        $parser = new Dummy();
        $parser->setContent($content);

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
        ], $parser->parse('initest/config.txt'));
    }
}