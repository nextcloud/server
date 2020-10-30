<?php

namespace LanguageServerProtocol;

class VersionedTextDocumentIdentifier extends TextDocumentIdentifier
{
    /**
     * The version number of this document.
     *
     * @var int
     */
    public $version;

    public function __construct(string $uri = null, int $version = null)
    {
        parent::__construct($uri);
        $this->version = $version;
    }
}
