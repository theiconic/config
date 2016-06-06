<?php

namespace TheIconic\Config\Parser;

use TheIconic\Config\Exception\ParserException;

/**
 * config file parser for ini files
 *
 * @package Shared\Helper\Config\Parser
 */
class Php extends AbstractParser
{

    /**
     * parses a php config file containing a config array
     *
     * @param string $file
     * @return array
     */
    public function parse($file)
    {
        $config = include($file);

        if (false === $config) {
            throw new ParserException(sprintf('Couldn\'t parse config file %s', $file));
        }

        return $config;
    }

}
