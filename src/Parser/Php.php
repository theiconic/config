<?php

namespace TheIconic\Config\Parser;

use TheIconic\Config\Exception\ParserException;
use Throwable;

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
        try {
            $config = include($file);

            if (!is_array($config)) {
                throw new ParserException(sprintf('Couldn\'t parse config file %s', $file));
            }
        } catch (Throwable $e) {
            throw new ParserException(sprintf('Couldn\'t parse config file %s', $file));
        }

        return $config;
    }
}
