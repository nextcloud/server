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
use SpomkyLabs\Pki\X509\Certificate\Extension\DistributionPoint\DistributionPoint;
use UnexpectedValueException;
use function count;

/**
 * Implements 'CRL Distribution Points' certificate extension.
 *
 * @see https://tools.ietf.org/html/rfc5280#section-4.2.1.13
 */
class CRLDistributionPointsExtension extends Extension implements Countable, IteratorAggregate
{
    /**
     * Distribution points.
     *
     * @var DistributionPoint[]
     */
    protected array $distributionPoints;

    protected function __construct(string $oid, bool $critical, DistributionPoint ...$distributionPoints)
    {
        parent::__construct($oid, $critical);
        $this->distributionPoints = $distributionPoints;
    }

    public static function create(bool $critical, DistributionPoint ...$distribution_points): self
    {
        return new self(self::OID_CRL_DISTRIBUTION_POINTS, $critical, ...$distribution_points);
    }

    /**
     * Get distribution points.
     *
     * @return DistributionPoint[]
     */
    public function distributionPoints(): array
    {
        return $this->distributionPoints;
    }

    /**
     * Get the number of distribution points.
     *
     * @see \Countable::count()
     */
    public function count(): int
    {
        return count($this->distributionPoints);
    }

    /**
     * Get iterator for distribution points.
     *
     * @see \IteratorAggregate::getIterator()
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->distributionPoints);
    }

    protected static function fromDER(string $data, bool $critical): static
    {
        $dps = array_map(
            static fn (UnspecifiedType $el) => DistributionPoint::fromASN1($el->asSequence()),
            UnspecifiedType::fromDER($data)->asSequence()->elements()
        );
        if (count($dps) === 0) {
            throw new UnexpectedValueException('CRLDistributionPoints must have at least one DistributionPoint.');
        }
        // late static bound, extended by Freshest CRL extension
        return static::create($critical, ...$dps);
    }

    protected function valueASN1(): Element
    {
        if (count($this->distributionPoints) === 0) {
            throw new LogicException('No distribution points.');
        }
        $elements = array_map(static fn (DistributionPoint $dp) => $dp->toASN1(), $this->distributionPoints);
        return Sequence::create(...$elements);
    }
}
