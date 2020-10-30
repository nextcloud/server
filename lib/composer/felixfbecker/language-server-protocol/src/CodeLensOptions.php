<?php

namespace LanguageServerProtocol;

/**
 * Code Lens options.
 */
class CodeLensOptions
{
    /**
     * Code lens has a resolve provider as well.
     *
     * @var bool|null
     */
    public $resolveProvider;

    public function __construct(bool $resolveProvider = null)
    {
        $this->resolveProvider = $resolveProvider;
    }
}
