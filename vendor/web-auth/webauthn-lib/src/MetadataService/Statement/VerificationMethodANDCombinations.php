<?php

declare(strict_types=1);

namespace Webauthn\MetadataService\Statement;

use JsonSerializable;

class VerificationMethodANDCombinations implements JsonSerializable
{
    /**
     * @param VerificationMethodDescriptor[] $verificationMethods
     */
    public function __construct(
        /** @readonly */
        public array $verificationMethods = []
    ) {
    }

    /**
     * @param VerificationMethodDescriptor[] $verificationMethods
     */
    public static function create(array $verificationMethods): self
    {
        return new self($verificationMethods);
    }

    /**
     * @deprecated since 4.7.0. Please use the {self::create} directly.
     * @infection-ignore-all
     */
    public function addVerificationMethodDescriptor(VerificationMethodDescriptor $verificationMethodDescriptor): self
    {
        $this->verificationMethods[] = $verificationMethodDescriptor;

        return $this;
    }

    /**
     * @return VerificationMethodDescriptor[]
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getVerificationMethods(): array
    {
        return $this->verificationMethods;
    }

    /**
     * @param array<string, mixed> $data
     * @deprecated since 4.7.0. Please use the symfony/serializer for converting the object.
     * @infection-ignore-all
     */
    public static function createFromArray(array $data): self
    {
        return self::create(
            array_map(
                static fn (array $datum): VerificationMethodDescriptor => VerificationMethodDescriptor::createFromArray(
                    $datum
                ),
                $data
            )
        );
    }

    /**
     * @return array<VerificationMethodDescriptor>
     */
    public function jsonSerialize(): array
    {
        trigger_deprecation(
            'web-auth/webauthn-bundle',
            '4.9.0',
            'The "%s" method is deprecated and will be removed in 5.0. Please use the serializer instead.',
            __METHOD__
        );
        return $this->verificationMethods;
    }
}
