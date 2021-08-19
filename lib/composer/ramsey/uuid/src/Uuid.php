<?php

/**
 * This file is part of the ramsey/uuid library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace Ramsey\Uuid;

use DateTimeInterface;
use Ramsey\Uuid\Codec\CodecInterface;
use Ramsey\Uuid\Converter\NumberConverterInterface;
use Ramsey\Uuid\Converter\TimeConverterInterface;
use Ramsey\Uuid\Fields\FieldsInterface;
use Ramsey\Uuid\Lazy\LazyUuidFromString;
use Ramsey\Uuid\Rfc4122\FieldsInterface as Rfc4122FieldsInterface;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Type\Integer as IntegerObject;

use function assert;
use function bin2hex;
use function preg_match;
use function str_replace;
use function strcmp;
use function strlen;
use function strtolower;
use function substr;

/**
 * Uuid provides constants and static methods for working with and generating UUIDs
 *
 * @psalm-immutable
 */
class Uuid implements UuidInterface
{
    use DeprecatedUuidMethodsTrait;

    /**
     * When this namespace is specified, the name string is a fully-qualified
     * domain name
     *
     * @link http://tools.ietf.org/html/rfc4122#appendix-C RFC 4122, Appendix C: Some Name Space IDs
     */
    public const NAMESPACE_DNS = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

    /**
     * When this namespace is specified, the name string is a URL
     *
     * @link http://tools.ietf.org/html/rfc4122#appendix-C RFC 4122, Appendix C: Some Name Space IDs
     */
    public const NAMESPACE_URL = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';

    /**
     * When this namespace is specified, the name string is an ISO OID
     *
     * @link http://tools.ietf.org/html/rfc4122#appendix-C RFC 4122, Appendix C: Some Name Space IDs
     */
    public const NAMESPACE_OID = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';

    /**
     * When this namespace is specified, the name string is an X.500 DN in DER
     * or a text output format
     *
     * @link http://tools.ietf.org/html/rfc4122#appendix-C RFC 4122, Appendix C: Some Name Space IDs
     */
    public const NAMESPACE_X500 = '6ba7b814-9dad-11d1-80b4-00c04fd430c8';

    /**
     * The nil UUID is a special form of UUID that is specified to have all 128
     * bits set to zero
     *
     * @link http://tools.ietf.org/html/rfc4122#section-4.1.7 RFC 4122, § 4.1.7: Nil UUID
     */
    public const NIL = '00000000-0000-0000-0000-000000000000';

    /**
     * Variant: reserved, NCS backward compatibility
     *
     * @link http://tools.ietf.org/html/rfc4122#section-4.1.1 RFC 4122, § 4.1.1: Variant
     */
    public const RESERVED_NCS = 0;

    /**
     * Variant: the UUID layout specified in RFC 4122
     *
     * @link http://tools.ietf.org/html/rfc4122#section-4.1.1 RFC 4122, § 4.1.1: Variant
     */
    public const RFC_4122 = 2;

    /**
     * Variant: reserved, Microsoft Corporation backward compatibility
     *
     * @link http://tools.ietf.org/html/rfc4122#section-4.1.1 RFC 4122, § 4.1.1: Variant
     */
    public const RESERVED_MICROSOFT = 6;

    /**
     * Variant: reserved for future definition
     *
     * @link http://tools.ietf.org/html/rfc4122#section-4.1.1 RFC 4122, § 4.1.1: Variant
     */
    public const RESERVED_FUTURE = 7;

    /**
     * @deprecated Use {@see ValidatorInterface::getPattern()} instead.
     */
    public const VALID_PATTERN = '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$';

    /**
     * Version 1 (time-based) UUID
     *
     * @link https://tools.ietf.org/html/rfc4122#section-4.1.3 RFC 4122, § 4.1.3: Version
     */
    public const UUID_TYPE_TIME = 1;

    /**
     * Version 2 (DCE Security) UUID
     *
     * @link https://tools.ietf.org/html/rfc4122#section-4.1.3 RFC 4122, § 4.1.3: Version
     */
    public const UUID_TYPE_DCE_SECURITY = 2;

    /**
     * @deprecated Use {@see Uuid::UUID_TYPE_DCE_SECURITY} instead.
     */
    public const UUID_TYPE_IDENTIFIER = 2;

    /**
     * Version 3 (name-based and hashed with MD5) UUID
     *
     * @link https://tools.ietf.org/html/rfc4122#section-4.1.3 RFC 4122, § 4.1.3: Version
     */
    public const UUID_TYPE_HASH_MD5 = 3;

    /**
     * Version 4 (random) UUID
     *
     * @link https://tools.ietf.org/html/rfc4122#section-4.1.3 RFC 4122, § 4.1.3: Version
     */
    public const UUID_TYPE_RANDOM = 4;

    /**
     * Version 5 (name-based and hashed with SHA1) UUID
     *
     * @link https://tools.ietf.org/html/rfc4122#section-4.1.3 RFC 4122, § 4.1.3: Version
     */
    public const UUID_TYPE_HASH_SHA1 = 5;

    /**
     * Version 6 (ordered-time) UUID
     *
     * This is named `UUID_TYPE_PEABODY`, since the specification is still in
     * draft form, and the primary author/editor's name is Brad Peabody.
     *
     * @link https://github.com/uuid6/uuid6-ietf-draft UUID version 6 IETF draft
     * @link http://gh.peabody.io/uuidv6/ "Version 6" UUIDs
     */
    public const UUID_TYPE_PEABODY = 6;

    /**
     * DCE Security principal domain
     *
     * @link https://pubs.opengroup.org/onlinepubs/9696989899/chap11.htm#tagcjh_14_05_01_01 DCE 1.1, §11.5.1.1
     */
    public const DCE_DOMAIN_PERSON = 0;

    /**
     * DCE Security group domain
     *
     * @link https://pubs.opengroup.org/onlinepubs/9696989899/chap11.htm#tagcjh_14_05_01_01 DCE 1.1, §11.5.1.1
     */
    public const DCE_DOMAIN_GROUP = 1;

    /**
     * DCE Security organization domain
     *
     * @link https://pubs.opengroup.org/onlinepubs/9696989899/chap11.htm#tagcjh_14_05_01_01 DCE 1.1, §11.5.1.1
     */
    public const DCE_DOMAIN_ORG = 2;

    /**
     * DCE Security domain string names
     *
     * @link https://pubs.opengroup.org/onlinepubs/9696989899/chap11.htm#tagcjh_14_05_01_01 DCE 1.1, §11.5.1.1
     */
    public const DCE_DOMAIN_NAMES = [
        self::DCE_DOMAIN_PERSON => 'person',
        self::DCE_DOMAIN_GROUP => 'group',
        self::DCE_DOMAIN_ORG => 'org',
    ];

    /**
     * @var UuidFactoryInterface|null
     */
    private static $factory = null;

    /**
     * @var bool flag to detect if the UUID factory was replaced internally, which disables all optimizations
     *           for the default/happy path internal scenarios
     */
    private static $factoryReplaced = false;

    /**
     * @var CodecInterface
     */
    protected $codec;

    /**
     * The fields that make up this UUID
     *
     * @var Rfc4122FieldsInterface
     */
    protected $fields;

    /**
     * @var NumberConverterInterface
     */
    protected $numberConverter;

    /**
     * @var TimeConverterInterface
     */
    protected $timeConverter;

    /**
     * Creates a universally unique identifier (UUID) from an array of fields
     *
     * Unless you're making advanced use of this library to generate identifiers
     * that deviate from RFC 4122, you probably do not want to instantiate a
     * UUID directly. Use the static methods, instead:
     *
     * ```
     * use Ramsey\Uuid\Uuid;
     *
     * $timeBasedUuid = Uuid::uuid1();
     * $namespaceMd5Uuid = Uuid::uuid3(Uuid::NAMESPACE_URL, 'http://php.net/');
     * $randomUuid = Uuid::uuid4();
     * $namespaceSha1Uuid = Uuid::uuid5(Uuid::NAMESPACE_URL, 'http://php.net/');
     * ```
     *
     * @param Rfc4122FieldsInterface $fields The fields from which to construct a UUID
     * @param NumberConverterInterface $numberConverter The number converter to use
     *     for converting hex values to/from integers
     * @param CodecInterface $codec The codec to use when encoding or decoding
     *     UUID strings
     * @param TimeConverterInterface $timeConverter The time converter to use
     *     for converting timestamps extracted from a UUID to unix timestamps
     */
    public function __construct(
        Rfc4122FieldsInterface $fields,
        NumberConverterInterface $numberConverter,
        CodecInterface $codec,
        TimeConverterInterface $timeConverter
    ) {
        $this->fields = $fields;
        $this->codec = $codec;
        $this->numberConverter = $numberConverter;
        $this->timeConverter = $timeConverter;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Converts the UUID to a string for JSON serialization
     */
    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    /**
     * Converts the UUID to a string for PHP serialization
     */
    public function serialize(): string
    {
        return $this->getFields()->getBytes();
    }

    /**
     * Re-constructs the object from its serialized form
     *
     * @param string $serialized The serialized PHP string to unserialize into
     *     a UuidInterface instance
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function unserialize($serialized): void
    {
        if (strlen($serialized) === 16) {
            /** @var Uuid $uuid */
            $uuid = self::getFactory()->fromBytes($serialized);
        } else {
            /** @var Uuid $uuid */
            $uuid = self::getFactory()->fromString($serialized);
        }

        $this->codec = $uuid->codec;
        $this->numberConverter = $uuid->numberConverter;
        $this->fields = $uuid->fields;
        $this->timeConverter = $uuid->timeConverter;
    }

    public function compareTo(UuidInterface $other): int
    {
        $compare = strcmp($this->toString(), $other->toString());

        if ($compare < 0) {
            return -1;
        }

        if ($compare > 0) {
            return 1;
        }

        return 0;
    }

    public function equals(?object $other): bool
    {
        if (!$other instanceof UuidInterface) {
            return false;
        }

        return $this->compareTo($other) === 0;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function getBytes(): string
    {
        return $this->codec->encodeBinary($this);
    }

    public function getFields(): FieldsInterface
    {
        return $this->fields;
    }

    public function getHex(): Hexadecimal
    {
        return new Hexadecimal(str_replace('-', '', $this->toString()));
    }

    public function getInteger(): IntegerObject
    {
        return new IntegerObject($this->numberConverter->fromHex($this->getHex()->toString()));
    }

    /**
     * @psalm-return non-empty-string
     */
    public function toString(): string
    {
        return $this->codec->encode($this);
    }

    /**
     * Returns the factory used to create UUIDs
     */
    public static function getFactory(): UuidFactoryInterface
    {
        if (self::$factory === null) {
            self::$factory = new UuidFactory();
        }

        return self::$factory;
    }

    /**
     * Sets the factory used to create UUIDs
     *
     * @param UuidFactoryInterface $factory A factory that will be used by this
     *     class to create UUIDs
     */
    public static function setFactory(UuidFactoryInterface $factory): void
    {
        // Note: non-strict equality is intentional here. If the factory is configured differently, every assumption
        //       around purity is broken, and we have to internally decide everything differently.
        // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedNotEqualOperator
        self::$factoryReplaced = ($factory != new UuidFactory());

        self::$factory = $factory;
    }

    /**
     * Creates a UUID from a byte string
     *
     * @param string $bytes A binary string
     *
     * @return UuidInterface A UuidInterface instance created from a binary
     *     string representation
     *
     * @psalm-pure note: changing the internal factory is an edge case not covered by purity invariants,
     *             but under constant factory setups, this method operates in functionally pure manners
     *
     * @psalm-suppress ImpureStaticProperty we know that the factory being replaced can lead to massive
     *                                      havoc across all consumers: that should never happen, and
     *                                      is generally to be discouraged. Until the factory is kept
     *                                      un-replaced, this method is effectively pure.
     */
    public static function fromBytes(string $bytes): UuidInterface
    {
        if (! self::$factoryReplaced && strlen($bytes) === 16) {
            $base16Uuid = bin2hex($bytes);

            // Note: we are calling `fromString` internally because we don't know if the given `$bytes` is a valid UUID
            return self::fromString(
                substr($base16Uuid, 0, 8)
                . '-'
                . substr($base16Uuid, 8, 4)
                . '-'
                . substr($base16Uuid, 12, 4)
                . '-'
                . substr($base16Uuid, 16, 4)
                . '-'
                . substr($base16Uuid, 20, 12)
            );
        }

        return self::getFactory()->fromBytes($bytes);
    }

    /**
     * Creates a UUID from the string standard representation
     *
     * @param string $uuid A hexadecimal string
     *
     * @return UuidInterface A UuidInterface instance created from a hexadecimal
     *     string representation
     *
     * @psalm-pure note: changing the internal factory is an edge case not covered by purity invariants,
     *             but under constant factory setups, this method operates in functionally pure manners
     *
     * @psalm-suppress ImpureStaticProperty we know that the factory being replaced can lead to massive
     *                                      havoc across all consumers: that should never happen, and
     *                                      is generally to be discouraged. Until the factory is kept
     *                                      un-replaced, this method is effectively pure.
     */
    public static function fromString(string $uuid): UuidInterface
    {
        if (! self::$factoryReplaced && preg_match(LazyUuidFromString::VALID_REGEX, $uuid) === 1) {
            assert($uuid !== '');

            return new LazyUuidFromString(strtolower($uuid));
        }

        return self::getFactory()->fromString($uuid);
    }

    /**
     * Creates a UUID from a DateTimeInterface instance
     *
     * @param DateTimeInterface $dateTime The date and time
     * @param Hexadecimal|null $node A 48-bit number representing the hardware
     *     address
     * @param int|null $clockSeq A 14-bit number used to help avoid duplicates
     *     that could arise when the clock is set backwards in time or if the
     *     node ID changes
     *
     * @return UuidInterface A UuidInterface instance that represents a
     *     version 1 UUID created from a DateTimeInterface instance
     */
    public static function fromDateTime(
        DateTimeInterface $dateTime,
        ?Hexadecimal $node = null,
        ?int $clockSeq = null
    ): UuidInterface {
        return self::getFactory()->fromDateTime($dateTime, $node, $clockSeq);
    }

    /**
     * Creates a UUID from a 128-bit integer string
     *
     * @param string $integer String representation of 128-bit integer
     *
     * @return UuidInterface A UuidInterface instance created from the string
     *     representation of a 128-bit integer
     *
     * @psalm-pure note: changing the internal factory is an edge case not covered by purity invariants,
     *             but under constant factory setups, this method operates in functionally pure manners
     */
    public static function fromInteger(string $integer): UuidInterface
    {
        return self::getFactory()->fromInteger($integer);
    }

    /**
     * Returns true if the provided string is a valid UUID
     *
     * @param string $uuid A string to validate as a UUID
     *
     * @return bool True if the string is a valid UUID, false otherwise
     *
     * @psalm-pure note: changing the internal factory is an edge case not covered by purity invariants,
     *             but under constant factory setups, this method operates in functionally pure manners
     */
    public static function isValid(string $uuid): bool
    {
        return self::getFactory()->getValidator()->validate($uuid);
    }

    /**
     * Returns a version 1 (time-based) UUID from a host ID, sequence number,
     * and the current time
     *
     * @param Hexadecimal|int|string|null $node A 48-bit number representing the
     *     hardware address; this number may be represented as an integer or a
     *     hexadecimal string
     * @param int $clockSeq A 14-bit number used to help avoid duplicates that
     *     could arise when the clock is set backwards in time or if the node ID
     *     changes
     *
     * @return UuidInterface A UuidInterface instance that represents a
     *     version 1 UUID
     */
    public static function uuid1($node = null, ?int $clockSeq = null): UuidInterface
    {
        return self::getFactory()->uuid1($node, $clockSeq);
    }

    /**
     * Returns a version 2 (DCE Security) UUID from a local domain, local
     * identifier, host ID, clock sequence, and the current time
     *
     * @param int $localDomain The local domain to use when generating bytes,
     *     according to DCE Security
     * @param IntegerObject|null $localIdentifier The local identifier for the
     *     given domain; this may be a UID or GID on POSIX systems, if the local
     *     domain is person or group, or it may be a site-defined identifier
     *     if the local domain is org
     * @param Hexadecimal|null $node A 48-bit number representing the hardware
     *     address
     * @param int|null $clockSeq A 14-bit number used to help avoid duplicates
     *     that could arise when the clock is set backwards in time or if the
     *     node ID changes (in a version 2 UUID, the lower 8 bits of this number
     *     are replaced with the domain).
     *
     * @return UuidInterface A UuidInterface instance that represents a
     *     version 2 UUID
     */
    public static function uuid2(
        int $localDomain,
        ?IntegerObject $localIdentifier = null,
        ?Hexadecimal $node = null,
        ?int $clockSeq = null
    ): UuidInterface {
        return self::getFactory()->uuid2($localDomain, $localIdentifier, $node, $clockSeq);
    }

    /**
     * Returns a version 3 (name-based) UUID based on the MD5 hash of a
     * namespace ID and a name
     *
     * @param string|UuidInterface $ns The namespace (must be a valid UUID)
     * @param string $name The name to use for creating a UUID
     *
     * @return UuidInterface A UuidInterface instance that represents a
     *     version 3 UUID
     *
     * @psalm-suppress ImpureMethodCall we know that the factory being replaced can lead to massive
     *                                  havoc across all consumers: that should never happen, and
     *                                  is generally to be discouraged. Until the factory is kept
     *                                  un-replaced, this method is effectively pure.
     *
     * @psalm-pure note: changing the internal factory is an edge case not covered by purity invariants,
     *             but under constant factory setups, this method operates in functionally pure manners
     */
    public static function uuid3($ns, string $name): UuidInterface
    {
        return self::getFactory()->uuid3($ns, $name);
    }

    /**
     * Returns a version 4 (random) UUID
     *
     * @return UuidInterface A UuidInterface instance that represents a
     *     version 4 UUID
     */
    public static function uuid4(): UuidInterface
    {
        return self::getFactory()->uuid4();
    }

    /**
     * Returns a version 5 (name-based) UUID based on the SHA-1 hash of a
     * namespace ID and a name
     *
     * @param string|UuidInterface $ns The namespace (must be a valid UUID)
     * @param string $name The name to use for creating a UUID
     *
     * @return UuidInterface A UuidInterface instance that represents a
     *     version 5 UUID
     *
     * @psalm-pure note: changing the internal factory is an edge case not covered by purity invariants,
     *             but under constant factory setups, this method operates in functionally pure manners
     *
     * @psalm-suppress ImpureMethodCall we know that the factory being replaced can lead to massive
     *                                  havoc across all consumers: that should never happen, and
     *                                  is generally to be discouraged. Until the factory is kept
     *                                  un-replaced, this method is effectively pure.
     */
    public static function uuid5($ns, string $name): UuidInterface
    {
        return self::getFactory()->uuid5($ns, $name);
    }

    /**
     * Returns a version 6 (ordered-time) UUID from a host ID, sequence number,
     * and the current time
     *
     * @param Hexadecimal|null $node A 48-bit number representing the hardware
     *     address
     * @param int $clockSeq A 14-bit number used to help avoid duplicates that
     *     could arise when the clock is set backwards in time or if the node ID
     *     changes
     *
     * @return UuidInterface A UuidInterface instance that represents a
     *     version 6 UUID
     */
    public static function uuid6(
        ?Hexadecimal $node = null,
        ?int $clockSeq = null
    ): UuidInterface {
        return self::getFactory()->uuid6($node, $clockSeq);
    }
}
