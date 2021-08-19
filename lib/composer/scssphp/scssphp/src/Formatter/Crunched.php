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
 * Crunched formatter
 *
 * @author Anthon Pang <anthon.pang@gmail.com>
 *
 * @deprecated since 1.4.0. Use the Compressed formatter instead.
 *
 * @internal
 */
class Crunched extends Formatter
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        @trigger_error('The Crunched formatter is deprecated since 1.4.0. Use the Compressed formatter instead.', E_USER_DEPRECATED);

        $this->indentLevel = 0;
        $this->indentChar = '  ';
        $this->break = '';
        $this->open = '{';
        $this->close = '}';
        $this->tagSeparator = ',';
        $this->assignSeparator = ':';
        $this->keepSemicolons = false;
    }

    /**
     * {@inheritdoc}
     */
    public function blockLines(OutputBlock $block)
    {
        $inner = $this->indentStr();

        $glue = $this->break . $inner;

        foreach ($block->lines as $index => $line) {
            if (substr($line, 0, 2) === '/*') {
                unset($block->lines[$index]);
            }
        }

        $this->write($inner . implode($glue, $block->lines));

        if (! empty($block->children)) {
            $this->write($this->break);
        }
    }

    /**
     * Output block selectors
     *
     * @param \ScssPhp\ScssPhp\Formatter\OutputBlock $block
     */
    protected function blockSelectors(OutputBlock $block)
    {
        assert(! empty($block->selectors));

        $inner = $this->indentStr();

        $this->write(
            $inner
            . implode(
                $this->tagSeparator,
                str_replace([' > ', ' + ', ' ~ '], ['>', '+', '~'], $block->selectors)
            )
            . $this->open . $this->break
        );
    }
}
