<?php

namespace TheIconic\Config\Parser;

/**
 * abstract config file parser
 *
 * @package Shared\Helper\Config\Parser
 */
abstract class AbstractParser
{

    /**
     * override on specific implementation
     *
     * @param string $file
     * @return mixed
     */
    abstract public function parse($file);
}
