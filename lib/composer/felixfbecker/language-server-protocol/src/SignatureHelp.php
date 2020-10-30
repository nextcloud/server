<?php

namespace LanguageServerProtocol;

/**
 * Signature help represents the signature of something
 * callable. There can be multiple signature but only one
 * active and only one active parameter.
 */
class SignatureHelp
{
    /**
     * One or more signatures.
     *
     * @var SignatureInformation[]
     */
    public $signatures;

    /**
     * The active signature.
     *
     * @var int|null
     */
    public $activeSignature;

    /**
     * The active parameter of the active signature.
     *
     * @var int|null
     */
    public $activeParameter;

    /**
     * Create a SignatureHelp
     *
     * @param SignatureInformation[] $signatures      List of signature information
     * @param int|null               $activeSignature The active signature, zero based
     * @param int|null               $activeParameter The active parameter, zero based
     */
    public function __construct(array $signatures = [], $activeSignature = null, int $activeParameter = null)
    {
        $this->signatures = $signatures;
        $this->activeSignature = $activeSignature;
        $this->activeParameter = $activeParameter;
    }
}
