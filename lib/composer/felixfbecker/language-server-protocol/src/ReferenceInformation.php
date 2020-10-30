<?php
declare(strict_types = 1);

namespace LanguageServerProtocol;

/**
 * Metadata about the symbol that can be used to identify or locate its
 * definition.
 */
class ReferenceInformation
{
    /**
     * The location in the workspace where the `symbol` is referenced.
     *
     * @var Location
     */
    public $reference;

    /**
     * Metadata about the symbol that can be used to identify or locate its
     * definition.
     *
     * @var SymbolDescriptor
     */
    public $symbol;

    /**
     * @param Location         $reference The location in the workspace where the `symbol` is referenced.
     * @param SymbolDescriptor $symbol    Metadata about the symbol that can be used to identify or locate its definition.
     */
    public function __construct(Location $reference = null, SymbolDescriptor $symbol = null)
    {
        $this->reference = $reference;
        $this->symbol = $symbol;
    }
}
