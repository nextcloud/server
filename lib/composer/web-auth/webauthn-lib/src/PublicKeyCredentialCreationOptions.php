<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Webauthn;

use Assert\Assertion;
use Base64Url\Base64Url;
use function count;
use function Safe\json_decode;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;

class PublicKeyCredentialCreationOptions extends PublicKeyCredentialOptions
{
    public const ATTESTATION_CONVEYANCE_PREFERENCE_NONE = 'none';
    public const ATTESTATION_CONVEYANCE_PREFERENCE_INDIRECT = 'indirect';
    public const ATTESTATION_CONVEYANCE_PREFERENCE_DIRECT = 'direct';
    public const ATTESTATION_CONVEYANCE_PREFERENCE_ENTERPRISE = 'enterprise';

    /**
     * @var PublicKeyCredentialRpEntity
     */
    private $rp;

    /**
     * @var PublicKeyCredentialUserEntity
     */
    private $user;

    /**
     * @var PublicKeyCredentialParameters[]
     */
    private $pubKeyCredParams = [];

    /**
     * @var PublicKeyCredentialDescriptor[]
     */
    private $excludeCredentials = [];

    /**
     * @var AuthenticatorSelectionCriteria
     */
    private $authenticatorSelection;

    /**
     * @var string
     */
    private $attestation;

    /**
     * @param PublicKeyCredentialParameters[] $pubKeyCredParams
     * @param PublicKeyCredentialDescriptor[] $excludeCredentials
     */
    public function __construct(PublicKeyCredentialRpEntity $rp, PublicKeyCredentialUserEntity $user, string $challenge, array $pubKeyCredParams, ?int $timeout = null, array $excludeCredentials = [], ?AuthenticatorSelectionCriteria $authenticatorSelection = null, ?string $attestation = null, ?AuthenticationExtensionsClientInputs $extensions = null)
    {
        if (0 !== count($excludeCredentials)) {
            @trigger_error('The argument "excludeCredentials" is deprecated since version 3.3 and will be removed in 4.0. Please use the method "excludeCredentials" or "excludeCredential".', E_USER_DEPRECATED);
        }
        if (null !== $authenticatorSelection) {
            @trigger_error('The argument "authenticatorSelection" is deprecated since version 3.3 and will be removed in 4.0. Please use the method "setAuthenticatorSelection".', E_USER_DEPRECATED);
        }
        if (null !== $attestation) {
            @trigger_error('The argument "attestation" is deprecated since version 3.3 and will be removed in 4.0. Please use the method "setAttestation".', E_USER_DEPRECATED);
        }
        parent::__construct($challenge, $timeout, $extensions);
        $this->rp = $rp;
        $this->user = $user;
        $this->pubKeyCredParams = $pubKeyCredParams;
        $this->authenticatorSelection = $authenticatorSelection ?? new AuthenticatorSelectionCriteria();
        $this->attestation = $attestation ?? self::ATTESTATION_CONVEYANCE_PREFERENCE_NONE;
        $this->excludeCredentials($excludeCredentials)
        ;
    }

    /**
     * @param PublicKeyCredentialParameters[] $pubKeyCredParams
     */
    public static function create(PublicKeyCredentialRpEntity $rp, PublicKeyCredentialUserEntity $user, string $challenge, array $pubKeyCredParams): self
    {
        return new self($rp, $user, $challenge, $pubKeyCredParams);
    }

    public function addPubKeyCredParam(PublicKeyCredentialParameters $pubKeyCredParam): self
    {
        $this->pubKeyCredParams[] = $pubKeyCredParam;

        return $this;
    }

    /**
     * @param PublicKeyCredentialParameters[] $pubKeyCredParams
     */
    public function addPubKeyCredParams(array $pubKeyCredParams): self
    {
        foreach ($pubKeyCredParams as $pubKeyCredParam) {
            $this->addPubKeyCredParam($pubKeyCredParam);
        }

        return $this;
    }

    public function excludeCredential(PublicKeyCredentialDescriptor $excludeCredential): self
    {
        $this->excludeCredentials[] = $excludeCredential;

        return $this;
    }

    /**
     * @param PublicKeyCredentialDescriptor[] $excludeCredentials
     */
    public function excludeCredentials(array $excludeCredentials): self
    {
        foreach ($excludeCredentials as $excludeCredential) {
            $this->excludeCredential($excludeCredential);
        }

        return $this;
    }

    public function setAuthenticatorSelection(AuthenticatorSelectionCriteria $authenticatorSelection): self
    {
        $this->authenticatorSelection = $authenticatorSelection;

        return $this;
    }

    public function setAttestation(string $attestation): self
    {
        Assertion::inArray($attestation, [
            self::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            self::ATTESTATION_CONVEYANCE_PREFERENCE_DIRECT,
            self::ATTESTATION_CONVEYANCE_PREFERENCE_INDIRECT,
            self::ATTESTATION_CONVEYANCE_PREFERENCE_ENTERPRISE,
        ], 'Invalid attestation conveyance mode');
        $this->attestation = $attestation;

        return $this;
    }

    public function getRp(): PublicKeyCredentialRpEntity
    {
        return $this->rp;
    }

    public function getUser(): PublicKeyCredentialUserEntity
    {
        return $this->user;
    }

    /**
     * @return PublicKeyCredentialParameters[]
     */
    public function getPubKeyCredParams(): array
    {
        return $this->pubKeyCredParams;
    }

    /**
     * @return PublicKeyCredentialDescriptor[]
     */
    public function getExcludeCredentials(): array
    {
        return $this->excludeCredentials;
    }

    public function getAuthenticatorSelection(): AuthenticatorSelectionCriteria
    {
        return $this->authenticatorSelection;
    }

    public function getAttestation(): string
    {
        return $this->attestation;
    }

    public static function createFromString(string $data): PublicKeyCredentialOptions
    {
        $data = json_decode($data, true);
        Assertion::isArray($data, 'Invalid data');

        return self::createFromArray($data);
    }

    public static function createFromArray(array $json): PublicKeyCredentialOptions
    {
        Assertion::keyExists($json, 'rp', 'Invalid input. "rp" is missing.');
        Assertion::keyExists($json, 'pubKeyCredParams', 'Invalid input. "pubKeyCredParams" is missing.');
        Assertion::isArray($json['pubKeyCredParams'], 'Invalid input. "pubKeyCredParams" is not an array.');
        Assertion::keyExists($json, 'challenge', 'Invalid input. "challenge" is missing.');
        Assertion::keyExists($json, 'attestation', 'Invalid input. "attestation" is missing.');
        Assertion::keyExists($json, 'user', 'Invalid input. "user" is missing.');
        Assertion::keyExists($json, 'authenticatorSelection', 'Invalid input. "authenticatorSelection" is missing.');

        $pubKeyCredParams = [];
        foreach ($json['pubKeyCredParams'] as $pubKeyCredParam) {
            $pubKeyCredParams[] = PublicKeyCredentialParameters::createFromArray($pubKeyCredParam);
        }
        $excludeCredentials = [];
        if (isset($json['excludeCredentials'])) {
            foreach ($json['excludeCredentials'] as $excludeCredential) {
                $excludeCredentials[] = PublicKeyCredentialDescriptor::createFromArray($excludeCredential);
            }
        }

        return self
            ::create(
                PublicKeyCredentialRpEntity::createFromArray($json['rp']),
                PublicKeyCredentialUserEntity::createFromArray($json['user']),
                Base64Url::decode($json['challenge']),
                $pubKeyCredParams
            )
                ->excludeCredentials($excludeCredentials)
                ->setAuthenticatorSelection(AuthenticatorSelectionCriteria::createFromArray($json['authenticatorSelection']))
                ->setAttestation($json['attestation'])
                ->setTimeout($json['timeout'] ?? null)
                ->setExtensions(isset($json['extensions']) ? AuthenticationExtensionsClientInputs::createFromArray($json['extensions']) : new AuthenticationExtensionsClientInputs())
        ;
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        $json = [
            'rp' => $this->rp->jsonSerialize(),
            'pubKeyCredParams' => array_map(static function (PublicKeyCredentialParameters $object): array {
                return $object->jsonSerialize();
            }, $this->pubKeyCredParams),
            'challenge' => Base64Url::encode($this->challenge),
            'attestation' => $this->attestation,
            'user' => $this->user->jsonSerialize(),
            'authenticatorSelection' => $this->authenticatorSelection->jsonSerialize(),
        ];

        if (0 !== count($this->excludeCredentials)) {
            $json['excludeCredentials'] = array_map(static function (PublicKeyCredentialDescriptor $object): array {
                return $object->jsonSerialize();
            }, $this->excludeCredentials);
        }

        if (0 !== $this->extensions->count()) {
            $json['extensions'] = $this->extensions;
        }

        if (null !== $this->timeout) {
            $json['timeout'] = $this->timeout;
        }

        return $json;
    }
}
