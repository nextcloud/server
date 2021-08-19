<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */

namespace ScssPhp\ScssPhp\Formatter;

use ScssPhp\ScssPhp\Formatter;

/**
 * Expanded formatter
 *
 * @author Leaf Corcoran <leafot@gmail.com>
 *
 * @internal
 */
class Expanded extends Formatter
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->indentLevel = 0;
        $this->indentChar = '  ';
        $this->break = "\n";
        $this->open = ' {';
        $this->close = '}';
        $this->tagSeparator = ', ';
        $this->assignSeparator = ': ';
        $this->keepSemicolons = true;
    }

    /**
     * {@inheritdoc}
     */
    protected function indentStr()
    {
        return str_repeat($this->indentChar, $this->indentLevel);
    }

    /**
     * {@inheritdoc}
     */
    protected function blockLines(OutputBlock $block)
    {
        $inner = $this->indentStr();

        $glue = $this->break . $inner;

        foreach ($block->lines as $index => $line) {
            if (substr($line, 0, 2) === '/*') {
                $block->lines[$index] = preg_replace('/\r\n?|\n|\f/', $this->break, $line);
            }
        }

        $this->write($inner . implode($glue, $block->lines));

        if (empty($block->selectors) || ! empty($block->children)) {
            $this->write($this->break);
        }
    }
}
