<?php

namespace LanguageServerProtocol;

class ClientCapabilities
{
    /**
     * The client supports workspace/xfiles requests
     *
     * @var bool|null
     */
    public $xfilesProvider;

    /**
     * The client supports textDocument/xcontent requests
     *
     * @var bool|null
     */
    public $xcontentProvider;

    /**
     * The client supports xcache/* requests
     *
     * @var bool|null
     */
    public $xcacheProvider;

    public function __construct(bool $xfilesProvider = null, bool $xcontentProvider = null, bool $xcacheProvider = null)
    {
        $this->xfilesProvider = $xfilesProvider;
        $this->xcontentProvider = $xcontentProvider;
        $this->xcacheProvider = $xcacheProvider;
    }
}
