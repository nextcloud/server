<?php

namespace LanguageServerProtocol;

class InitializeResult
{
    /**
     * The capabilities the language server provides.
     *
     * @var ServerCapabilities
     */
    public $capabilities;

    /**
     * @param ServerCapabilities $capabilities
     */
    public function __construct(ServerCapabilities $capabilities = null)
    {
        $this->capabilities = $capabilities ?? new ServerCapabilities();
    }
}
