<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\DocBlock;

use PhpCsFixer\Preg;

/**
 * This represents a tag, as defined by the proposed PSR PHPDoc standard.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class Tag
{
    /**
     * All the tags defined by the proposed PSR PHPDoc standard.
     *
     * @var string[]
     */
    private static $tags = [
        'api', 'author', 'category', 'copyright', 'deprecated', 'example',
        'global', 'internal', 'license', 'link', 'method', 'package', 'param',
        'property', 'property-read', 'property-write', 'return', 'see',
        'since', 'subpackage', 'throws', 'todo', 'uses', 'var', 'version',
    ];

    /**
     * The line containing the tag.
     *
     * @var Line
     */
    private $line;

    /**
     * The cached tag name.
     *
     * @var null|string
     */
    private $name;

    /**
     * Create a new tag instance.
     */
    public function __construct(Line $line)
    {
        $this->line = $line;
    }

    /**
     * Get the tag name.
     *
     * This may be "param", or "return", etc.
     *
     * @return string
     */
    public function getName()
    {
        if (null === $this->name) {
            Preg::matchAll('/@[a-zA-Z0-9_-]+(?=\s|$)/', $this->line->getContent(), $matches);

            if (isset($matches[0][0])) {
                $this->name = ltrim($matches[0][0], '@');
            } else {
                $this->name = 'other';
            }
        }

        return $this->name;
    }

    /**
     * Set the tag name.
     *
     * This will also be persisted to the upstream line and annotation.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $current = $this->getName();

        if ('other' === $current) {
            throw new \RuntimeException('Cannot set name on unknown tag.');
        }

        $this->line->setContent(Preg::replace("/@{$current}/", "@{$name}", $this->line->getContent(), 1));

        $this->name = $name;
    }

    /**
     * Is the tag a known tag?
     *
     * This is defined by if it exists in the proposed PSR PHPDoc standard.
     *
     * @return bool
     */
    public function valid()
    {
        return \in_array($this->getName(), self::$tags, true);
    }
}
