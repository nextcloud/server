<?php

namespace LanguageServerProtocol;

/**
 * Signature help options.
 */
class SignatureHelpOptions
{
    /**
     * The characters that trigger signature help automatically.
     *
     * @var string[]|null
     */
    public $triggerCharacters;

    public function __construct(array $triggerCharacters = null)
    {
        $this->triggerCharacters = $triggerCharacters;
    }
}
