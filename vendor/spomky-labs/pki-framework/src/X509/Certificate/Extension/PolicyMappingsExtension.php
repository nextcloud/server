<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\Certificate\Extension;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\X509\Certificate\Extension\CertificatePolicy\PolicyInformation;
use SpomkyLabs\Pki\X509\Certificate\Extension\PolicyMappings\PolicyMapping;
use UnexpectedValueException;
use function count;

/**
 * Implements 'Policy Mappings' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.5
 */
final class PolicyMappingsExtension extends Extension implements Countable, IteratorAggregate
{
    /**
     * Policy mappings.
     *
     * @var PolicyMapping[]
     */
    private readonly array $mappings;

    /**
     * @param PolicyMapping ...$mappings One or more PolicyMapping objects
     */
    private function __construct(bool $critical, PolicyMapping ...$mappings)
    {
        parent::__construct(self::OID_POLICY_MAPPINGS, $critical);
        $this->mappings = $mappings;
    }

    public static function create(bool $critical, PolicyMapping ...$mappings): self
    {
        return new self($critical, ...$mappings);
    }

    /**
     * Get all mappings.
     *
     * @return PolicyMapping[]
     */
    public function mappings(): array
    {
        return $this->mappings;
    }

    /**
     * Get mappings flattened into a single array of arrays of subject domains keyed by issuer domain.
     *
     * Eg. if policy mappings contains multiple mappings with the same issuer domain policy, their corresponding subject
     * domain policies are placed under the same key.
     *
     * @return (string[])[]
     */
    public function flattenedMappings(): array
    {
        $mappings = [];
        foreach ($this->mappings as $mapping) {
            $idp = $mapping->issuerDomainPolicy();
            if (! isset($mappings[$idp])) {
                $mappings[$idp] = [];
            }
            array_push($mappings[$idp], $mapping->subjectDomainPolicy());
        }
        return $mappings;
    }

    /**
     * Get all subject domain policy OIDs that are mapped to given issuer domain policy OID.
     *
     * @param string $oid Issuer domain policy
     *
     * @return string[] List of OIDs in dotted format
     */
    public function issuerMappings(string $oid): array
    {
        $oids = [];
        foreach ($this->mappings as $mapping) {
            if ($mapping->issuerDomainPolicy() === $oid) {
                $oids[] = $mapping->subjectDomainPolicy();
            }
        }
        return $oids;
    }

    /**
     * Get all mapped issuer domain policy OIDs.
     *
     * @return string[]
     */
    public function issuerDomainPolicies(): array
    {
        $idps = array_map(static fn (PolicyMapping $mapping) => $mapping->issuerDomainPolicy(), $this->mappings);
        return array_values(array_unique($idps));
    }

    /**
     * Check whether policy mappings have anyPolicy mapped.
     *
     * RFC 5280 section 4.2.1.5 states that "Policies MUST NOT be mapped either to or from the special value anyPolicy".
     */
    public function hasAnyPolicyMapping(): bool
    {
        foreach ($this->mappings as $mapping) {
            if ($mapping->issuerDomainPolicy() === PolicyInformation::OID_ANY_POLICY) {
                return true;
            }
            if ($mapping->subjectDomainPolicy() === PolicyInformation::OID_ANY_POLICY) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the number of mappings.
     *
     * @see \Countable::count()
     */
    public function count(): int
    {
        return count($this->mappings);
    }

    /**
     * Get iterator for policy mappings.
     *
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->mappings);
    }

    protected static function fromDER(string $data, bool $critical): static
    {
        $mappings = array_map(
            static fn (UnspecifiedType $el) => PolicyMapping::fromASN1($el->asSequence()),
            UnspecifiedType::fromDER($data)->asSequence()->elements()
        );
        if (count($mappings) === 0) {
            throw new UnexpectedValueException('PolicyMappings must have at least one mapping.');
        }
        return self::create($critical, ...$mappings);
    }

    protected function valueASN1(): Element
    {
        if (count($this->mappings) === 0) {
            throw new LogicException('No mappings.');
        }
        $elements = array_map(static fn (PolicyMapping $mapping) => $mapping->toASN1(), $this->mappings);
        return Sequence::create(...$elements);
    }
}
