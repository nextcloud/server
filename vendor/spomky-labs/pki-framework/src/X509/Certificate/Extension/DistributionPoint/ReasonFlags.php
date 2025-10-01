<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension\DistributionPoint;

use SpomkyLabs\Pki\ASN1\Type\Primitive\BitString;
use SpomkyLabs\Pki\ASN1\Util\Flags;

/**
 * Implements *ReasonFlags* ASN.1 type used by 'CRL Distribution Points' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.13
 */
final class ReasonFlags
{
    // const UNUSED = 0x100;
    final public const KEY_COMPROMISE = 0x080;

    final public const CA_COMPROMISE = 0x040;

    final public const AFFILIATION_CHANGED = 0x020;

    final public const SUPERSEDED = 0x010;

    final public const CESSATION_OF_OPERATION = 0x008;

    final public const CERTIFICATE_HOLD = 0x004;

    final public const PRIVILEGE_WITHDRAWN = 0x002;

    final public const AA_COMPROMISE = 0x001;

    private function __construct(
        private readonly int $flags
    ) {
    }

    public static function create(int $flags): self
    {
        return new self($flags);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(BitString $bs): self
    {
        return self::create(Flags::fromBitString($bs, 9)->intNumber());
    }

    /**
     * Check whether keyCompromise flag is set.
     */
    public function isKeyCompromise(): bool
    {
        return $this->flagSet(self::KEY_COMPROMISE);
    }

    /**
     * Check whether cACompromise flag is set.
     */
    public function isCACompromise(): bool
    {
        return $this->flagSet(self::CA_COMPROMISE);
    }

    /**
     * Check whether affiliationChanged flag is set.
     */
    public function isAffiliationChanged(): bool
    {
        return $this->flagSet(self::AFFILIATION_CHANGED);
    }

    /**
     * Check whether superseded flag is set.
     */
    public function isSuperseded(): bool
    {
        return $this->flagSet(self::SUPERSEDED);
    }

    /**
     * Check whether cessationOfOperation flag is set.
     */
    public function isCessationOfOperation(): bool
    {
        return $this->flagSet(self::CESSATION_OF_OPERATION);
    }

    /**
     * Check whether certificateHold flag is set.
     */
    public function isCertificateHold(): bool
    {
        return $this->flagSet(self::CERTIFICATE_HOLD);
    }

    /**
     * Check whether privilegeWithdrawn flag is set.
     */
    public function isPrivilegeWithdrawn(): bool
    {
        return $this->flagSet(self::PRIVILEGE_WITHDRAWN);
    }

    /**
     * Check whether aACompromise flag is set.
     */
    public function isAACompromise(): bool
    {
        return $this->flagSet(self::AA_COMPROMISE);
    }

    /**
     * Generate ASN.1 element.
     */
    public function toASN1(): BitString
    {
        $flags = Flags::create($this->flags, 9);
        return $flags->bitString()
            ->withoutTrailingZeroes();
    }

    /**
     * Check whether given flag is set.
     */
    private function flagSet(int $flag): bool
    {
        return (bool) ($this->flags & $flag);
    }
}
