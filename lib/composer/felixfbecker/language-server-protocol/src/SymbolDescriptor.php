<?php
declare(strict_types = 1);

namespace LanguageServerProtocol;

/**
 * Uniquely identifies a symbol
 */
class SymbolDescriptor
{
    /**
     * The fully qualified structural element name, a globally unique identifier for the symbol.
     *
     * @var string
     */
    public $fqsen;

    /**
     * Identifies the Composer package the symbol is defined in (if any)
     *
     * @var PackageDescriptor|null
     */
    public $package;

    /**
     * @param string $fqsen              The fully qualified structural element name, a globally unique identifier for the symbol.
     * @param PackageDescriptor $package Identifies the Composer package the symbol is defined in
     */
    public function __construct(string $fqsen = null, PackageDescriptor $package = null)
    {
        $this->fqsen = $fqsen;
        $this->package = $package;
    }
}
