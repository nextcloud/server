<?php
declare(strict_types = 1);

namespace LanguageServerProtocol;

class SymbolLocationInformation
{
    /**
     * The location where the symbol is defined, if any.
     *
     * @var Location|null
     */
    public $location;

    /**
     * Metadata about the symbol that can be used to identify or locate its
     * definition.
     *
     * @var SymbolDescriptor
     */
    public $symbol;

    /**
     * @param SymbolDescriptor $symbol   The location where the symbol is defined, if any
     * @param Location         $location Metadata about the symbol that can be used to identify or locate its definition
     */
    public function __construct(SymbolDescriptor $symbol = null, Location $location = null)
    {
        $this->symbol = $symbol;
        $this->location = $location;
    }
}
