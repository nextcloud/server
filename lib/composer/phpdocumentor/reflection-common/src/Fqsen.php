<?php

declare(strict_types=1);

/**
 * phpDocumentor
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link      http://phpdoc.org
 */

namespace phpDocumentor\Reflection;

use InvalidArgumentException;
use function assert;
use function end;
use function explode;
use function is_string;
use function preg_match;
use function sprintf;
use function trim;

/**
 * Value Object for Fqsen.
 *
 * @link https://github.com/phpDocumentor/fig-standards/blob/master/proposed/phpdoc-meta.md
 *
 * @psalm-immutable
 */
final class Fqsen
{
    /** @var string full quallified class name */
    private $fqsen;

    /** @var string name of the element without path. */
    private $name;

    /**
     * Initializes the object.
     *
     * @throws InvalidArgumentException when $fqsen is not matching the format.
     */
    public function __construct(string $fqsen)
    {
        $matches = [];

        $result = preg_match(
            //phpcs:ignore Generic.Files.LineLength.TooLong
            '/^\\\\([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff\\\\]*)?(?:[:]{2}\\$?([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*))?(?:\\(\\))?$/',
            $fqsen,
            $matches
        );

        if ($result === 0) {
            throw new InvalidArgumentException(
                sprintf('"%s" is not a valid Fqsen.', $fqsen)
            );
        }

        $this->fqsen = $fqsen;

        if (isset($matches[2])) {
            $this->name = $matches[2];
        } else {
            $matches = explode('\\', $fqsen);
            $name = end($matches);
            assert(is_string($name));
            $this->name = trim($name, '()');
        }
    }

    /**
     * converts this class to string.
     */
    public function __toString() : string
    {
        return $this->fqsen;
    }

    /**
     * Returns the name of the element without path.
     */
    public function getName() : string
    {
        return $this->name;
    }
}
