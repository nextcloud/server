<?php

namespace LanguageServerProtocol;

/**
 * Represents information about programming constructs like variables, classes,
 * interfaces etc.
 */
class SymbolInformation
{
    /**
     * The name of this symbol.
     *
     * @var string
     */
    public $name;

    /**
     * The kind of this symbol.
     *
     * @var int
     */
    public $kind;

    /**
     * The location of this symbol.
     *
     * @var Location
     */
    public $location;

    /**
     * The name of the symbol containing this symbol.
     *
     * @var string|null
     */
    public $containerName;

    /**
     * @param string $name
     * @param int $kind
     * @param Location $location
     * @param string $containerName
     */
    public function __construct($name = null, $kind = null, $location = null, $containerName = null)
    {
        $this->name = $name;
        $this->kind = $kind;
        $this->location = $location;
        $this->containerName = $containerName;
    }
}
