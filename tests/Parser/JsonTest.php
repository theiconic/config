<?php

namespace TheIconic\Config\Parser;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use TheIconic\Config\Exception\ParserException;

/**
 * test Php parser
 *
 * @package TheIconic\Config\Parser
 */
class JsonTest extends TestCase
{
    /**
     * test parse()
     */
    public function testParse()
    {
        $content = <<<EOF
{
  "all": {
    "test": {
      "key1": "abc"
    }
  },
  "dev": {
    "key2": "def"
  },
  "live": {
    "test": {
      "live": {
        "key3": true
      }
    }
  }
}
EOF;

        $root = vfsStream::setup('jsontest');
        vfsStream::newFile('config.json')
            ->at($root)
            ->withContent($content);

        $parser = new Json();

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
        ], $parser->parse(vfsStream::url('jsontest/config.json')));
    }

    /**
     * test parse()
     */
    public function testParseInvalidFile()
    {
        $content = <<<EOF
abcdefg=asdf=asdf=
asdfasdf
{asdfasfd}
EOF;

        $root = vfsStream::setup('jsontest');
        vfsStream::newFile('config.json')
            ->at($root)
            ->withContent($content);

        $parser = new Json();

        $this->expectException(ParserException::class);

        $parser->parse(vfsStream::url('jsontest/config.json'));
    }

    /**
     * test parse()
     */
    public function testParseInvalidConfig()
    {
        $content = 'true';

        $root = vfsStream::setup('jsontest');
        vfsStream::newFile('config.json')
            ->at($root)
            ->withContent($content);

        $parser = new Json();

        $this->expectException(ParserException::class);

        $parser->parse(vfsStream::url('jsontest/config.json'));
    }
}
