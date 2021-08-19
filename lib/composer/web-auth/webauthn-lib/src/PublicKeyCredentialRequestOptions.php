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

class PublicKeyCredentialRequestOptions extends PublicKeyCredentialOptions
{
    public const USER_VERIFICATION_REQUIREMENT_REQUIRED = 'required';
    public const USER_VERIFICATION_REQUIREMENT_PREFERRED = 'preferred';
    public const USER_VERIFICATION_REQUIREMENT_DISCOURAGED = 'discouraged';

    /**
     * @var string|null
     */
    private $rpId;

    /**
     * @var PublicKeyCredentialDescriptor[]
     */
    private $allowCredentials = [];

    /**
     * @var string|null
     */
    private $userVerification;

    /**
     * @param PublicKeyCredentialDescriptor[] $allowCredentials
     */
    public function __construct(string $challenge, ?int $timeout = null, ?string $rpId = null, array $allowCredentials = [], ?string $userVerification = null, ?AuthenticationExtensionsClientInputs $extensions = null)
    {
        if (0 !== count($allowCredentials)) {
            @trigger_error('The argument "allowCredentials" is deprecated since version 3.3 and will be removed in 4.0. Please use the method "addAllowedCredentials" or "addAllowedCredential".', E_USER_DEPRECATED);
        }
        if (null !== $rpId) {
            @trigger_error('The argument "rpId" is deprecated since version 3.3 and will be removed in 4.0. Please use the method "setRpId".', E_USER_DEPRECATED);
        }
        if (null !== $userVerification) {
            @trigger_error('The argument "userVerification" is deprecated since version 3.3 and will be removed in 4.0. Please use the method "setUserVerification".', E_USER_DEPRECATED);
        }
        parent::__construct($challenge, $timeout, $extensions);
        $this
            ->setRpId($rpId)
            ->allowCredentials($allowCredentials)
            ->setUserVerification($userVerification)
        ;
    }

    public static function create(string $challenge): self
    {
        return new self($challenge);
    }

    public function setRpId(?string $rpId): self
    {
        $this->rpId = $rpId;

        return $this;
    }

    public function allowCredential(PublicKeyCredentialDescriptor $allowCredential): self
    {
        $this->allowCredentials[] = $allowCredential;

        return $this;
    }

    /**
     * @param PublicKeyCredentialDescriptor[] $allowCredentials
     */
    public function allowCredentials(array $allowCredentials): self
    {
        foreach ($allowCredentials as $allowCredential) {
            $this->allowCredential($allowCredential);
        }

        return $this;
    }

    public function setUserVerification(?string $userVerification): self
    {
        if (null === $userVerification) {
            $this->rpId = null;

            return $this;
        }
        Assertion::inArray($userVerification, [
            self::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            self::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            self::USER_VERIFICATION_REQUIREMENT_DISCOURAGED,
        ], 'Invalid user verification requirement');
        $this->userVerification = $userVerification;

        return $this;
    }

    public function getRpId(): ?string
    {
        return $this->rpId;
    }

    /**
     * @return PublicKeyCredentialDescriptor[]
     */
    public function getAllowCredentials(): array
    {
        return $this->allowCredentials;
    }

    public function getUserVerification(): ?string
    {
        return $this->userVerification;
    }

    public static function createFromString(string $data): PublicKeyCredentialOptions
    {
        $data = json_decode($data, true);
        Assertion::isArray($data, 'Invalid data');

        return self::createFromArray($data);
    }

    /**
     * @param mixed[] $json
     */
    public static function createFromArray(array $json): PublicKeyCredentialOptions
    {
        Assertion::keyExists($json, 'challenge', 'Invalid input. "challenge" is missing.');

        $allowCredentials = [];
        $allowCredentialList = $json['allowCredentials'] ?? [];
        foreach ($allowCredentialList as $allowCredential) {
            $allowCredentials[] = PublicKeyCredentialDescriptor::createFromArray($allowCredential);
        }

        return self::create(Base64Url::decode($json['challenge']))
            ->setRpId($json['rpId'] ?? null)
            ->allowCredentials($allowCredentials)
            ->setUserVerification($json['userVerification'] ?? null)
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
            'challenge' => Base64Url::encode($this->challenge),
        ];

        if (null !== $this->rpId) {
            $json['rpId'] = $this->rpId;
        }

        if (null !== $this->userVerification) {
            $json['userVerification'] = $this->userVerification;
        }

        if (0 !== count($this->allowCredentials)) {
            $json['allowCredentials'] = array_map(static function (PublicKeyCredentialDescriptor $object): array {
                return $object->jsonSerialize();
            }, $this->allowCredentials);
        }

        if (0 !== $this->extensions->count()) {
            $json['extensions'] = $this->extensions->jsonSerialize();
        }

        if (null !== $this->timeout) {
            $json['timeout'] = $this->timeout;
        }

        return $json;
    }
}
