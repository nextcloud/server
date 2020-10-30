<?php

namespace LanguageServerProtocol;

/**
 * An event describing a file change.
 */
class FileEvent
{
    /**
     * The file's URI.
     *
     * @var string
     */
    public $uri;

    /**
     * The change type.
     *
     * @var int
     */
    public $type;

    /**
     * @param string $uri
     * @param int $type
     */
    public function __construct(string $uri, int $type)
    {
        $this->uri = $uri;
        $this->type = $type;
    }
}
