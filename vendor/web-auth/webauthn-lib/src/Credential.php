<?php

declare(strict_types=1);

namespace Webauthn;

use InvalidArgumentException;
use ParagonIE\ConstantTime\Base64UrlSafe;

/**
 * @see https://w3c.github.io/webappsec-credential-management/#credential
 */
abstract class Credential
{
    /**
     * @deprecated since 4.9.0. Please use the property rawId instead.
     */
    public readonly string $id;

    public readonly string $rawId;

    public function __construct(
        null|string $id,
        public readonly string $type,
        null|string $rawId = null,
    ) {
        if ($id === null && $rawId === null) {
            throw new InvalidArgumentException('You must provide a valid raw ID');
        }
        if ($id !== null) {
            trigger_deprecation(
                'web-auth/webauthn-lib',
                '4.9.0',
                'The property "$id" is deprecated and will be removed in 5.0.0. Please set null use "rawId" instead.'
            );
        } else {
            $id = Base64UrlSafe::encodeUnpadded($rawId);
        }
        $this->id = $id;
        $this->rawId = $rawId ?? Base64UrlSafe::decodeNoPadding($id);
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @deprecated since 4.7.0. Please use the property directly.
     * @infection-ignore-all
     */
    public function getType(): string
    {
        return $this->type;
    }
}
