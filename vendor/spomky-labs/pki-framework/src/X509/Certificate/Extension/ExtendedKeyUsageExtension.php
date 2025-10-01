<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\ObjectIdentifier;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use function count;
use function in_array;

/**
 * Implements 'Extended Key Usage' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.12
 */
final class ExtendedKeyUsageExtension extends Extension implements Countable, IteratorAggregate
{
    final public const OID_SERVER_AUTH = '1.3.6.1.5.5.7.3.1';

    final public const OID_CLIENT_AUTH = '1.3.6.1.5.5.7.3.2';

    final public const OID_CODE_SIGNING = '1.3.6.1.5.5.7.3.3';

    final public const OID_EMAIL_PROTECTION = '1.3.6.1.5.5.7.3.4';

    final public const OID_IPSEC_END_SYSTEM = '1.3.6.1.5.5.7.3.5';

    final public const OID_IPSEC_TUNNEL = '1.3.6.1.5.5.7.3.6';

    final public const OID_IPSEC_USER = '1.3.6.1.5.5.7.3.7';

    final public const OID_TIME_STAMPING = '1.3.6.1.5.5.7.3.8';

    final public const OID_OCSP_SIGNING = '1.3.6.1.5.5.7.3.9';

    final public const OID_DVCS = '1.3.6.1.5.5.7.3.10';

    final public const OID_SBGP_CERT_AA_SERVER_AUTH = '1.3.6.1.5.5.7.3.11';

    final public const OID_SCVP_RESPONDER = '1.3.6.1.5.5.7.3.12';

    final public const OID_EAP_OVER_PPP = '1.3.6.1.5.5.7.3.13';

    final public const OID_EAP_OVER_LAN = '1.3.6.1.5.5.7.3.14';

    final public const OID_SCVP_SERVER = '1.3.6.1.5.5.7.3.15';

    final public const OID_SCVP_CLIENT = '1.3.6.1.5.5.7.3.16';

    final public const OID_IPSEC_IKE = '1.3.6.1.5.5.7.3.17';

    final public const OID_CAPWAP_AC = '1.3.6.1.5.5.7.3.18';

    final public const OID_CAPWAP_WTP = '1.3.6.1.5.5.7.3.19';

    final public const OID_SIP_DOMAIN = '1.3.6.1.5.5.7.3.20';

    final public const OID_SECURE_SHELL_CLIENT = '1.3.6.1.5.5.7.3.21';

    final public const OID_SECURE_SHELL_SERVER = '1.3.6.1.5.5.7.3.22';

    final public const OID_SEND_ROUTER = '1.3.6.1.5.5.7.3.23';

    final public const OID_SEND_PROXY = '1.3.6.1.5.5.7.3.24';

    final public const OID_SEND_OWNER = '1.3.6.1.5.5.7.3.25';

    final public const OID_SEND_PROXIED_OWNER = '1.3.6.1.5.5.7.3.26';

    final public const OID_CMC_CA = '1.3.6.1.5.5.7.3.27';

    final public const OID_CMC_RA = '1.3.6.1.5.5.7.3.28';

    final public const OID_CMC_ARCHIVE = '1.3.6.1.5.5.7.3.29';

    /**
     * Purpose OID's.
     *
     * @var string[]
     */
    private readonly array $purposes;

    private function __construct(bool $critical, string ...$purposes)
    {
        parent::__construct(self::OID_EXT_KEY_USAGE, $critical);
        $this->purposes = $purposes;
    }

    public static function create(bool $critical, string ...$purposes): self
    {
        return new self($critical, ...$purposes);
    }

    /**
     * Whether purposes are present.
     *
     * If multiple purposes are checked, all must be present.
     */
    public function has(string ...$oids): bool
    {
        foreach ($oids as $oid) {
            if (! in_array($oid, $this->purposes, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get key usage purpose OID's.
     *
     * @return string[]
     */
    public function purposes(): array
    {
        return $this->purposes;
    }

    /**
     * Get the number of purposes.
     *
     * @see \Countable::count()
     */
    public function count(): int
    {
        return count($this->purposes);
    }

    /**
     * Get iterator for usage purposes.
     *
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->purposes);
    }

    protected static function fromDER(string $data, bool $critical): static
    {
        $purposes = array_map(
            static fn (UnspecifiedType $el) => $el->asObjectIdentifier()
                ->oid(),
            UnspecifiedType::fromDER($data)->asSequence()->elements()
        );
        return self::create($critical, ...$purposes);
    }

    protected function valueASN1(): Element
    {
        $elements = array_map(static fn ($oid) => ObjectIdentifier::create($oid), $this->purposes);
        return Sequence::create(...$elements);
    }
}
