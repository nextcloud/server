<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Formatter;

/**
 * Formatter style interface for defining styles.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface OutputFormatterStyleInterface
{
    /**
     * Sets style foreground color.
     *
     * @return void
     */
    public function setForeground(?string $color);

    /**
     * Sets style background color.
     *
     * @return void
     */
    public function setBackground(?string $color);

    /**
     * Sets some specific style option.
     *
     * @return void
     */
    public function setOption(string $option);

    /**
     * Unsets some specific style option.
     *
     * @return void
     */
    public function unsetOption(string $option);

    /**
     * Sets multiple style options at once.
     *
     * @return void
     */
    public function setOptions(array $options);

    /**
     * Applies the style to a given text.
     */
    public function apply(string $text): string;
}
