<?php

declare(strict_types=1);

namespace Webauthn;

use InvalidArgumentException;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticationExtensions\AuthenticationExtensions;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\Exception\InvalidDataException;
use Webauthn\Util\Base64;
use function array_key_exists;
use function count;
use function in_array;
use function is_array;
use const JSON_THROW_ON_ERROR;

final class PublicKeyCredentialCreationOptions extends PublicKeyCredentialOptions
{
    public const ATTESTATION_CONVEYANCE_PREFERENCE_DEFAULT = null;

    public const ATTESTATION_CONVEYANCE_PREFERENCE_NONE = 'none';

    public const ATTESTATION_CONVEYANCE_PREFERENCE_INDIRECT = 'indirect';

    public const ATTESTATION_CONVEYANCE_PREFERENCE_DIRECT = 'direct';

    public const ATTESTATION_CONVEYANCE_PREFERENCE_ENTERPRISE = 'enterprise';

    public const ATTESTATION_CONVEYANCE_PREFERENCES = [
        self::ATTESTATION_CONVEYANCE_PREFERENCE_DEFAULT,
        self::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
        self::ATTESTATION_CONVEYANCE_PREFERENCE_INDIRECT,
        self::ATTESTATION_CONVEYANCE_PREFERENCE_DIRECT,
        self::ATTESTATION_CONVEYANCE_PREFERENCE_ENTERPRISE,
    ];

    /**
     * @private
     * @param PublicKeyCredentialParameters[] $pubKeyCredParams
     * @param PublicKeyCredentialDescriptor[] $excludeCredentials
     * @param null|positive-int $timeout
     */
    public function __construct(
        public readonly PublicKeyCredentialRpEntity $rp,
        public readonly PublicKeyCredentialUserEntity $user,
        string $challenge,
        public array $pubKeyCredParams = [],
        public null|AuthenticatorSelectionCriteria $authenticatorSelection = null,
        public null|string $attestation = null,
        public array $excludeCredentials = [],
        null|int $timeout = null,
        null|AuthenticationExtensions $extensions = null,
    ) {
        foreach ($pubKeyCredParams as $pubKeyCredParam) {
            $pubKeyCredParam instanceof PublicKeyCredentialParameters || throw new InvalidArgumentException(
                'Invalid type for $pubKeyCredParams'
            );
        }
        foreach ($excludeCredentials as $excludeCredential) {
            $excludeCredential instanceof PublicKeyCredentialDescriptor || throw new InvalidArgumentException(
                'Invalid type for $excludeCredentials'
            );
        }
        in_array($attestation, self::ATTESTATION_CONVEYANCE_PREFERENCES, true) || throw InvalidDataException::create(
            $attestation,
            'Invalid attestation conveyance mode'
        );

        parent::__construct($challenge, $timeout, $extensions);
    }

    /**
     * @param PublicKeyCredentialParameters[] $pubKeyCredParams
     * @param PublicKeyCredentialDescriptor[] $excludeCredentials
     * @param null|positive-int $timeout
     */
    public static function create(
        PublicKeyCredentialRpEntity $rp,
        PublicKeyCredentialUserEntity $user,
        string $challenge,
        array $pubKeyCredParams = [],
        null|AuthenticatorSelectionCriteria $authenticatorSelection = null,
        null|string $attestation = null,
        array $excludeCredentials = [],
        null|int $timeout = null,
        null|AuthenticationExtensions $extensions = null,
    ): self {
        return new self(
            $rp,
            $user,
            $challenge,
            $pubKeyCredParams,
            $authenticatorSelection,
            $attestation,
            $excludeCredentials,
            $timeout,
            $extensions
        );
    }

    /**
     * @deprecated since 4.7.0. Please use the {self::create} instead.
     * @infection-ignore-all
     */
    public function addPubKeyCredParam(PublicKeyCredentialParameters $pubKeyCredParam): self
    {
        $this->pubKeyCredParams[] = $pubKeyCredParam;

        return $this;
    }

    /**
     * @deprecated since 4.7.0. No replacement. Please use the {self::create} instead.
     * @infection-ignore-all
     */
    public function addPubKeyCredParams(PublicKeyCredentialParameters ...$pubKeyCredParams): self
    {
        foreach ($pubKeyCredParams as $pubKeyCredParam) {
            $this->pubKeyCredParams[] = $pubKeyCredParam;
        }

        return $this;
    }

    /**
     * @deprecated since 4.7.0. Please use the {self::create} instead.
     * @infection-ignore-all
     */
    public function excludeCredential(PublicKeyCredentialDescriptor $excludeCredential): self
    {
        $this->excludeCredentials[] = $excludeCredential;

        return $this;
    }

    /**
     * @deprecated since 4.7.0. No replacement. Please use the {self::create} instead.
     * @infection-ignore-all
     */
    public function excludeCredentials(PublicKeyCredentialDescriptor ...$excludeCredentials): self
    {
        foreach ($excludeCredentials as $excludeCredential) {
            $this->excludeCredentials[] = $excludeCredential;
        }

        return $this;
    }

    /**
     * @deprecated since 4.7.0. Please use the {self::create} instead.
     * @infection-ignore-all
     */
    public function setAuthenticatorSelection(?AuthenticatorSelectionCriteria $authenticatorSelection): self
    {
        $this->authenticatorSelection = $authenticatorSelection;

        return $this;
    }

    /**
     * @deprecated since 4.7.0. Please use the {self::create} instead.
     * @infection-ignore-all
     */
    public function setAttestation(string $attestation): self
    {
        in_array($attestation, self::ATTESTATION_CONVEYANCE_PREFERENCES, true) || throw InvalidDataException::create(
            $attestation,
            'Invalid attestation conveyance mode'
        );
        $this->attestation = $attestation;

        return $this;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getRp(): PublicKeyCredentialRpEntity
    {
        return $this->rp;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getUser(): PublicKeyCredentialUserEntity
    {
        return $this->user;
    }

    /**
     * @return PublicKeyCredentialParameters[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getPubKeyCredParams(): array
    {
        return $this->pubKeyCredParams;
    }

    /**
     * @return PublicKeyCredentialDescriptor[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getExcludeCredentials(): array
    {
        return $this->excludeCredentials;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getAuthenticatorSelection(): ?AuthenticatorSelectionCriteria
    {
        return $this->authenticatorSelection;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getAttestation(): ?string
    {
        return $this->attestation;
    }

    /**
     * @deprecated since 4.8.0. Please use {Webauthn\Denormalizer\WebauthnSerializerFactory} for converting the object.
     * @infection-ignore-all
     */
    public static function createFromString(string $data): static
    {
        $data = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

        return self::createFromArray($data);
    }

    /**
     * @deprecated since 4.8.0. Please use {Webauthn\Denormalizer\WebauthnSerializerFactory} for converting the object.
     * @infection-ignore-all
     */
    public static function createFromArray(array $json): static
    {
        array_key_exists('rp', $json) || throw InvalidDataException::create($json, 'Invalid input. "rp" is missing.');
        array_key_exists('pubKeyCredParams', $json) || throw InvalidDataException::create(
            $json,
            'Invalid input. "pubKeyCredParams" is missing.'
        );
        is_array($json['pubKeyCredParams']) || throw InvalidDataException::create(
            $json,
            'Invalid input. "pubKeyCredParams" is not an array.'
        );
        array_key_exists('challenge', $json) || throw InvalidDataException::create(
            $json,
            'Invalid input. "challenge" is missing.'
        );
        array_key_exists('attestation', $json) || throw InvalidDataException::create(
            $json,
            'Invalid input. "attestation" is missing.'
        );
        array_key_exists('user', $json) || throw InvalidDataException::create(
            $json,
            'Invalid input. "user" is missing.'
        );

        $pubKeyCredParams = [];
        foreach ($json['pubKeyCredParams'] as $pubKeyCredParam) {
            if (! is_array($pubKeyCredParam)) {
                continue;
            }
            $pubKeyCredParams[] = PublicKeyCredentialParameters::createFromArray($pubKeyCredParam);
        }
        $excludeCredentials = [];
        if (isset($json['excludeCredentials'])) {
            foreach ($json['excludeCredentials'] as $excludeCredential) {
                $excludeCredentials[] = PublicKeyCredentialDescriptor::createFromArray($excludeCredential);
            }
        }

        $challenge = Base64::decode($json['challenge']);

        $authenticatorSelection = isset($json['authenticatorSelection']) ? AuthenticatorSelectionCriteria::createFromArray(
            $json['authenticatorSelection']
        ) : null
        ;
        $extensions =
            isset($json['extensions']) ? AuthenticationExtensionsClientInputs::createFromArray(
                $json['extensions']
            ) : AuthenticationExtensionsClientInputs::create()
        ;
        return self
            ::create(
                PublicKeyCredentialRpEntity::createFromArray($json['rp']),
                PublicKeyCredentialUserEntity::createFromArray($json['user']),
                $challenge,
                $pubKeyCredParams,
                $authenticatorSelection,
                $json['attestation'] ?? null,
                $excludeCredentials,
                $json['timeout'] ?? null,
                $extensions
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
        $json = [
            'rp' => $this->rp,
            'user' => $this->user,
            'challenge' => Base64UrlSafe::encodeUnpadded($this->challenge),
            'pubKeyCredParams' => $this->pubKeyCredParams,
        ];

        if ($this->timeout !== null) {
            $json['timeout'] = $this->timeout;
        }

        if (count($this->excludeCredentials) !== 0) {
            $json['excludeCredentials'] = $this->excludeCredentials;
        }

        if ($this->authenticatorSelection !== null) {
            $json['authenticatorSelection'] = $this->authenticatorSelection;
        }

        if ($this->attestation !== null) {
            $json['attestation'] = $this->attestation;
        }

        if ($this->extensions->count() !== 0) {
            $json['extensions'] = $this->extensions;
        }

        return $json;
    }
}
