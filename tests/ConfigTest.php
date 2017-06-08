<?php

namespace TheIconic\Config;

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testGetReturnsDefault()
    {
        $config = new Config([]);
        $this->assertSame('abcd', $config->get('non_existing_key', 'abcd'));
    }

    public function testGetWithEmptyKey()
    {
        $data = [
            'test1' => 'abc',
            'test2' => 'def',
        ];

        $config = new Config($data);

        $this->assertSame($data, $config->get());
    }

    public function testFlatten()
    {
        $config = new Config([
            'test' => [
                'key1' => 'abc',
            ],
            'key2' => 'bcd',
            'test1' => [
                'test2' => [
                    'key3' => 3,
                    'key4' => 4,
                ],
                'key5' => 5,
            ]
        ]);

        $this->assertSame([
            'test.key1' => 'abc',
            'key2' => 'bcd',
            'test1.test2.key3' => 3,
            'test1.test2.key4' => 4,
            'test1.key5' => 5
        ], $config->flatten());
    }
}