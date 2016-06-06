<?php

namespace TheIconic\Config\Parser;

/**
 * dummy configuration file parser - useful for testing
 *
 * @package Shared\Helper\Config\Parser
 */
class Dummy extends AbstractParser
{

    /**
     * @var array the 'parsed' content
     */
    protected $content = array();

    /**
     * returns the set content
     *
     * @param $file
     * @return array
     */
    public function parse($file)
    {
        return $this->content;
    }

    /**
     * get the content
     *
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * set the content
     *
     * @param array $content
     * @return $this
     */
    public function setContent(array $content)
    {
        $this->content = $content;

        return $this;
    }

}
