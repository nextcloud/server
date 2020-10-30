<?php

namespace LanguageServerProtocol;

/**
 * An item to transfer a text document from the client to the server.
 */
class TextDocumentItem
{
    /**
     * The text document's URI.
     *
     * @var string
     */
    public $uri;

    /**
     * The text document's language identifier.
     *
     * @var string
     */
    public $languageId;

    /**
     * The version number of this document (it will strictly increase after each
     * change, including undo/redo).
     *
     * @var int
     */
    public $version;

    /**
     * The content of the opened text document.
     *
     * @var string
     */
    public $text;

    public function __construct(string $uri = null, string $languageId = null, int $version = null, string $text = null)
    {
        $this->uri = $uri;
        $this->languageId = $languageId;
        $this->version = $version;
        $this->text = $text;
    }
}
