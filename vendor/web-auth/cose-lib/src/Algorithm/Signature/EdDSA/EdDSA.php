<?php

declare(strict_types=1);

namespace Cose\Algorithm\Signature\EdDSA;

use Cose\Algorithm\Signature\Signature;
use Cose\Algorithms;
use Cose\Key\Key;
use Cose\Key\OkpKey;
use InvalidArgumentException;
use Throwable;
use function sodium_crypto_sign_detached;
use function sodium_crypto_sign_verify_detached;

/**
 * @see \Cose\Tests\Algorithm\Signature\EdDSA\EdDSATest
 */
class EdDSA implements Signature
{
    public function sign(string $data, Key $key): string
    {
        $key = $this->handleKey($key);
        if (! $key->isPrivate()) {
            throw new InvalidArgumentException('The key is not private.');
        }

        $x = $key->x();
        $d = $key->d();
        $secret = $d . $x;

        return match ($key->curve()) {
            OkpKey::CURVE_ED25519 => sodium_crypto_sign_detached($data, $secret),
            OkpKey::CURVE_NAME_ED25519 => sodium_crypto_sign_detached($data, $secret),
            default => throw new InvalidArgumentException('Unsupported curve'),
        };
    }

    public function verify(string $data, Key $key, string $signature): bool
    {
        $key = $this->handleKey($key);
        if ($key->curve() !== OkpKey::CURVE_ED25519 && $key->curve() !== OkpKey::CURVE_NAME_ED25519) {
            throw new InvalidArgumentException('Unsupported curve');
        }
        try {
            sodium_crypto_sign_verify_detached($signature, $data, $key->x());
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    public static function identifier(): int
    {
        return Algorithms::COSE_ALGORITHM_EDDSA;
    }

    private function handleKey(Key $key): OkpKey
    {
        return OkpKey::create($key->getData());
    }
}
