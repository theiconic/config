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
        ]);

        $this->assertSame([
            'test.key1' => 'abc',
            'key2' => 'bcd',
        ], $config->flatten());
    }
}