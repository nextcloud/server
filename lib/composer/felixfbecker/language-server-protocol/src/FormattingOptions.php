<?php

namespace LanguageServerProtocol;

/**
 * Value-object describing what options formatting should use.
 */
class FormattingOptions
{
    /**
     * Size of a tab in spaces.
     *
     * @var int
     */
    public $tabSize;

    /**
     * Prefer spaces over tabs.
     *
     * @var bool
     */
    public $insertSpaces;

    // Can be extended with further properties.

    public function __construct(int $tabSize = null, bool $insertSpaces = null)
    {
        $this->tabSize = $tabSize;
        $this->insertSpaces = $insertSpaces;
    }
}
