<?php

namespace LanguageServerProtocol;

/**
 * Contains additional diagnostic information about the context in which
 * a code action is run.
 */
class CodeActionContext
{
    /**
     * An array of diagnostics.
     *
     * @var Diagnostic[]
     */
    public $diagnostics;

    /**
     * @param Diagnostic[] $diagnostics
     */
    public function __construct(array $diagnostics = [])
    {
        $this->diagnostics = $diagnostics;
    }
}
