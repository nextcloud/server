<?php

declare(strict_types=1);

namespace Cose\Algorithm\Signature\ECDSA;

use Cose\Algorithm\Signature\Signature;
use Cose\Key\Ec2Key;
use Cose\Key\Key;
use InvalidArgumentException;
use function openssl_sign;
use function openssl_verify;

/**
 * @see \Cose\Tests\Algorithm\Signature\ECDSA\ECDSATest
 */
abstract class ECDSA implements Signature
{
    public function sign(string $data, Key $key): string
    {
        $key = $this->handleKey($key);
        openssl_sign($data, $signature, $key->asPEM(), $this->getHashAlgorithm());

        return ECSignature::fromAsn1($signature, $this->getSignaturePartLength());
    }

    public function verify(string $data, Key $key, string $signature): bool
    {
        $key = $this->handleKey($key);
        $publicKey = $key->toPublic();
        $signature = ECSignature::toAsn1($signature, $this->getSignaturePartLength());
        return openssl_verify($data, $signature, $publicKey->asPEM(), $this->getHashAlgorithm()) === 1;
    }

    abstract protected function getCurve(): int;

    abstract protected function getHashAlgorithm(): int;

    abstract protected function getSignaturePartLength(): int;

    private function handleKey(Key $key): Ec2Key
    {
        $key = Ec2Key::create($key->getData());
        if ($key->curve() !== $this->getCurve()) {
            throw new InvalidArgumentException('This key cannot be used with this algorithm');
        }

        return $key;
    }
}
