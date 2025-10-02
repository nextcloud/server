<?php

declare(strict_types=1);

namespace Webauthn\AttestationStatement;

use JsonSerializable;
use Webauthn\Exception\InvalidDataException;
use Webauthn\TrustPath\TrustPath;
use Webauthn\TrustPath\TrustPathLoader;
use function array_key_exists;

class AttestationStatement implements JsonSerializable
{
    final public const TYPE_NONE = 'none';

    final public const TYPE_BASIC = 'basic';

    final public const TYPE_SELF = 'self';

    final public const TYPE_ATTCA = 'attca';

    /**
     * @deprecated since 4.2.0 and will be removed in 5.0.0. The ECDAA Trust Anchor does no longer exist in Webauthn specification.
     * @infection-ignore-all
     */
    final public const TYPE_ECDAA = 'ecdaa';

    final public const TYPE_ANONCA = 'anonca';

    /**
     * @param array<string, mixed> $attStmt
     */
    public function __construct(
        public readonly string $fmt,
        public readonly array $attStmt,
        public readonly string $type,
        public readonly TrustPath $trustPath
    ) {
    }

    public static function create(string $fmt, array $attStmt, string $type, TrustPath $trustPath): self
    {
        return new self($fmt, $attStmt, $type, $trustPath);
    }

    /**
     * @param array<string, mixed> $attStmt
     */
    public static function createNone(string $fmt, array $attStmt, TrustPath $trustPath): self
    {
        return self::create($fmt, $attStmt, self::TYPE_NONE, $trustPath);
    }

    /**
     * @param array<string, mixed> $attStmt
     */
    public static function createBasic(string $fmt, array $attStmt, TrustPath $trustPath): self
    {
        return self::create($fmt, $attStmt, self::TYPE_BASIC, $trustPath);
    }

    /**
     * @param array<string, mixed> $attStmt
     */
    public static function createSelf(string $fmt, array $attStmt, TrustPath $trustPath): self
    {
        return self::create($fmt, $attStmt, self::TYPE_SELF, $trustPath);
    }

    /**
     * @param array<string, mixed> $attStmt
     */
    public static function createAttCA(string $fmt, array $attStmt, TrustPath $trustPath): self
    {
        return self::create($fmt, $attStmt, self::TYPE_ATTCA, $trustPath);
    }

    /**
     * @param array<string, mixed> $attStmt
     *
     * @deprecated since 4.2.0 and will be removed in 5.0.0. The ECDAA Trust Anchor does no longer exist in Webauthn specification.
     * @infection-ignore-all
     */
    public static function createEcdaa(string $fmt, array $attStmt, TrustPath $trustPath): self
    {
        return self::create($fmt, $attStmt, self::TYPE_ECDAA, $trustPath);
    }

    /**
     * @param array<string, mixed> $attStmt
     */
    public static function createAnonymizationCA(string $fmt, array $attStmt, TrustPath $trustPath): self
    {
        return self::create($fmt, $attStmt, self::TYPE_ANONCA, $trustPath);
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getFmt(): string
    {
        return $this->fmt;
    }

    /**
     * @return mixed[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getAttStmt(): array
    {
        return $this->attStmt;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attStmt);
    }

    public function get(string $key): mixed
    {
        $this->has($key) || throw InvalidDataException::create($this->attStmt, sprintf(
            'The attestation statement has no key "%s".',
            $key
        ));

        return $this->attStmt[$key];
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getTrustPath(): TrustPath
    {
        return $this->trustPath;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param mixed[] $data
     * @deprecated since 4.8.0. Please use {Webauthn\Denormalizer\WebauthnSerializerFactory} for converting the object.
     * @infection-ignore-all
     */
    public static function createFromArray(array $data): self
    {
        foreach (['fmt', 'attStmt', 'trustPath', 'type'] as $key) {
            array_key_exists($key, $data) || throw InvalidDataException::create($data, sprintf(
                'The key "%s" is missing',
                $key
            ));
        }

        return self::create(
            $data['fmt'],
            $data['attStmt'],
            $data['type'],
            TrustPathLoader::loadTrustPath($data['trustPath'])
        );
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        trigger_deprecation(
            'web-auth/webauthn-bundle',
            '4.9.0',
            'The "%s" method is deprecated and will be removed in 5.0. Please use the serializer instead.',
            __METHOD__
        );
        return [
            'fmt' => $this->fmt,
            'attStmt' => $this->attStmt,
            'trustPath' => $this->trustPath,
            'type' => $this->type,
        ];
    }
}
