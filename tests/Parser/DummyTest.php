<?php

namespace TheIconic\Config\Parser;

use PHPUnit\Framework\TestCase;

/**
 * test Dummy parser
 *
 * @package TheIconic\Config\Parser
 */
class DummyTest extends TestCase
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
        
        $this->assertSame($content, $parser->getContent());

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
