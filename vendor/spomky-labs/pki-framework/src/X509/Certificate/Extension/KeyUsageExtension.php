<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension;

use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\ASN1\Util\Flags;

/**
 * Implements 'Key Usage' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.3
 */
final class KeyUsageExtension extends Extension
{
    final public const DIGITAL_SIGNATURE = 0x100;

    final public const NON_REPUDIATION = 0x080;

    final public const KEY_ENCIPHERMENT = 0x040;

    final public const DATA_ENCIPHERMENT = 0x020;

    final public const KEY_AGREEMENT = 0x010;

    final public const KEY_CERT_SIGN = 0x008;

    final public const CRL_SIGN = 0x004;

    final public const ENCIPHER_ONLY = 0x002;

    final public const DECIPHER_ONLY = 0x001;

    private function __construct(
        bool $critical,
        private readonly int $keyUsage
    ) {
        parent::__construct(self::OID_KEY_USAGE, $critical);
    }

    public static function create(bool $critical, int $keyUsage): self
    {
        return new self($critical, $keyUsage);
    }

    /**
     * Check whether digitalSignature flag is set.
     */
    public function isDigitalSignature(): bool
    {
        return $this->_flagSet(self::DIGITAL_SIGNATURE);
    }

    /**
     * Check whether nonRepudiation/contentCommitment flag is set.
     */
    public function isNonRepudiation(): bool
    {
        return $this->_flagSet(self::NON_REPUDIATION);
    }

    /**
     * Check whether keyEncipherment flag is set.
     */
    public function isKeyEncipherment(): bool
    {
        return $this->_flagSet(self::KEY_ENCIPHERMENT);
    }

    /**
     * Check whether dataEncipherment flag is set.
     */
    public function isDataEncipherment(): bool
    {
        return $this->_flagSet(self::DATA_ENCIPHERMENT);
    }

    /**
     * Check whether keyAgreement flag is set.
     */
    public function isKeyAgreement(): bool
    {
        return $this->_flagSet(self::KEY_AGREEMENT);
    }

    /**
     * Check whether keyCertSign flag is set.
     */
    public function isKeyCertSign(): bool
    {
        return $this->_flagSet(self::KEY_CERT_SIGN);
    }

    /**
     * Check whether cRLSign flag is set.
     */
    public function isCRLSign(): bool
    {
        return $this->_flagSet(self::CRL_SIGN);
    }

    /**
     * Check whether encipherOnly flag is set.
     */
    public function isEncipherOnly(): bool
    {
        return $this->_flagSet(self::ENCIPHER_ONLY);
    }

    /**
     * Check whether decipherOnly flag is set.
     */
    public function isDecipherOnly(): bool
    {
        return $this->_flagSet(self::DECIPHER_ONLY);
    }

    /**
     * Check whether given flag is set.
     */
    protected function _flagSet(int $flag): bool
    {
        return (bool) ($this->keyUsage & $flag);
    }

    protected static function fromDER(string $data, bool $critical): static
    {
        return self::create(
            $critical,
            Flags::fromBitString(UnspecifiedType::fromDER($data)->asBitString(), 9)->intNumber()
        );
    }

    protected function valueASN1(): Element
    {
        $flags = Flags::create($this->keyUsage, 9);
        return $flags->bitString()
            ->withoutTrailingZeroes();
    }
}
