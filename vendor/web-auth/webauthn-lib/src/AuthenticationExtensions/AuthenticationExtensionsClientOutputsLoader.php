<?php

declare(strict_types=1);

namespace Webauthn\AuthenticationExtensions;

use CBOR\CBORObject;
use CBOR\MapObject;
use Webauthn\Exception\AuthenticationExtensionException;

abstract class AuthenticationExtensionsClientOutputsLoader
{
    public static function load(CBORObject $object): AuthenticationExtensions
    {
        $object instanceof MapObject || throw AuthenticationExtensionException::create('Invalid extension object');
        $data = $object->normalize();
        return AuthenticationExtensionsClientOutputs::create(
            array_map(
                fn (mixed $value, string $key) => AuthenticationExtension::create($key, $value),
                $data,
                array_keys($data)
            )
        );
    }
}
