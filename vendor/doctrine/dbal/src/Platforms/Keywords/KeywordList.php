<?php

namespace Doctrine\DBAL\Platforms\Keywords;

use function array_flip;
use function array_map;
use function strtoupper;

/**
 * Abstract interface for a SQL reserved keyword dictionary.
 *
 * @phpstan-consistent-constructor
 */
abstract class KeywordList
{
    /** @var string[]|null */
    private ?array $keywords = null;

    /**
     * Checks if the given word is a keyword of this dialect/vendor platform.
     *
     * @param string $word
     *
     * @return bool
     */
    public function isKeyword($word)
    {
        if ($this->keywords === null) {
            $this->initializeKeywords();
        }

        return isset($this->keywords[strtoupper($word)]);
    }

    /** @return void */
    protected function initializeKeywords()
    {
        $this->keywords = array_flip(array_map('strtoupper', $this->getKeywords()));
    }

    /**
     * Returns the list of keywords.
     *
     * @return string[]
     */
    abstract protected function getKeywords();

    /**
     * Returns the name of this keyword list.
     *
     * @deprecated
     *
     * @return string
     */
    abstract public function getName();
}
