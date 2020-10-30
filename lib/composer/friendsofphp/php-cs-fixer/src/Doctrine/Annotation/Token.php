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

namespace PhpCsFixer\Doctrine\Annotation;

use Doctrine\Common\Annotations\DocLexer;

/**
 * A Doctrine annotation token.
 *
 * @internal
 */
final class Token
{
    /**
     * @var int
     */
    private $type;

    /**
     * @var string
     */
    private $content;

    /**
     * @param int    $type    The type
     * @param string $content The content
     */
    public function __construct($type = DocLexer::T_NONE, $content = '')
    {
        $this->type = $type;
        $this->content = $content;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Returns whether the token type is one of the given types.
     *
     * @param int|int[] $types
     *
     * @return bool
     */
    public function isType($types)
    {
        if (!\is_array($types)) {
            $types = [$types];
        }

        return \in_array($this->getType(), $types, true);
    }

    /**
     * Overrides the content with an empty string.
     */
    public function clear()
    {
        $this->setContent('');
    }
}
