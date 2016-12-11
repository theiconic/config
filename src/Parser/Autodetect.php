<?php

namespace TheIconic\Config\Parser;

use TheIconic\Config\Exception\ParserException;
use Exception;

/**
 * auto-detecting parser - invokes specific parser based on config file extension
 *
 * @package Shared\Helper\Config\Parser
 */
class Autodetect extends AbstractParser
{
    /**
     * @var array
     */
    protected $parsers = [];

    /**
     * invokes specific parser based on config file extension
     *
     * @param string $file
     * @return array
     * @throws Exception
     */
    public function parse($file)
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        $parsers = $this->getParsers();

        if (!isset($parsers[$extension])) {
            throw new ParserException(sprintf('No parser found for config file with extension \'%s\'', $extension));
        }

        /** @var AbstractParser $parser */
        $parser = $parsers[$extension];

        return $parser->parse($file);
    }

    /**
     * @return array
     */
    public function getParsers(): array
    {
        if (empty($this->parsers)) {
            $this->parsers = [
                'ini' => new Ini(),
                'php' => new Php(),
                'json' => new Json(),
            ];
        }

        return $this->parsers;
    }

    /**
     * @param array $parsers
     * @return Autodetect
     */
    public function setParsers(array $parsers): Autodetect
    {
        $this->parsers = $parsers;

        return $this;
    }
}
