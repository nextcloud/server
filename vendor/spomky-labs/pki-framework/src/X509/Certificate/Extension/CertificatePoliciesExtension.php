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
use UnexpectedValueException;
use function count;

/**
 * Implements 'Certificate Policies' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.4
 */
final class CertificatePoliciesExtension extends Extension implements Countable, IteratorAggregate
{
    /**
     * Policy information terms.
     *
     * @var PolicyInformation[]
     */
    protected array $_policies;

    private function __construct(bool $critical, PolicyInformation ...$policies)
    {
        parent::__construct(Extension::OID_CERTIFICATE_POLICIES, $critical);
        $this->_policies = [];
        foreach ($policies as $policy) {
            $this->_policies[$policy->oid()] = $policy;
        }
    }

    public static function create(bool $critical, PolicyInformation ...$policies): self
    {
        return new self($critical, ...$policies);
    }

    /**
     * Check whether policy information by OID is present.
     */
    public function has(string $oid): bool
    {
        return isset($this->_policies[$oid]);
    }

    /**
     * Get policy information by OID.
     */
    public function get(string $oid): PolicyInformation
    {
        if (! $this->has($oid)) {
            throw new LogicException("Not certificate policy by OID {$oid}.");
        }
        return $this->_policies[$oid];
    }

    /**
     * Check whether anyPolicy is present.
     */
    public function hasAnyPolicy(): bool
    {
        return $this->has(PolicyInformation::OID_ANY_POLICY);
    }

    /**
     * Get anyPolicy information.
     */
    public function anyPolicy(): PolicyInformation
    {
        if (! $this->hasAnyPolicy()) {
            throw new LogicException('No anyPolicy.');
        }
        return $this->get(PolicyInformation::OID_ANY_POLICY);
    }

    /**
     * Get the number of policies.
     *
     * @see \Countable::count()
     */
    public function count(): int
    {
        return count($this->_policies);
    }

    /**
     * Get iterator for policy information terms.
     *
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->_policies);
    }

    protected static function fromDER(string $data, bool $critical): static
    {
        $policies = array_map(
            static fn (UnspecifiedType $el) => PolicyInformation::fromASN1($el->asSequence()),
            UnspecifiedType::fromDER($data)->asSequence()->elements()
        );
        if (count($policies) === 0) {
            throw new UnexpectedValueException('certificatePolicies must contain at least one PolicyInformation.');
        }
        return self::create($critical, ...$policies);
    }

    protected function valueASN1(): Element
    {
        if (count($this->_policies) === 0) {
            throw new LogicException('No policies.');
        }
        $elements = array_map(static fn (PolicyInformation $pi) => $pi->toASN1(), array_values($this->_policies));
        return Sequence::create(...$elements);
    }
}
