<?php

declare(strict_types=1);

namespace Cose\Algorithm\Mac;

use Cose\Key\Key;
use Cose\Key\SymmetricKey;
use InvalidArgumentException;

/**
 * @see \Cose\Tests\Algorithm\Mac\HmacTest
 */
abstract class Hmac implements Mac
{
    public function hash(string $data, Key $key): string
    {
        $this->checKey($key);
        $signature = hash_hmac($this->getHashAlgorithm(), $data, (string) $key->get(SymmetricKey::DATA_K), true);

        return mb_substr($signature, 0, intdiv($this->getSignatureLength(), 8), '8bit');
    }

    public function verify(string $data, Key $key, string $signature): bool
    {
        return hash_equals($this->hash($data, $key), $signature);
    }

    abstract protected function getHashAlgorithm(): string;

    abstract protected function getSignatureLength(): int;

    private function checKey(Key $key): void
    {
        if ($key->type() !== Key::TYPE_OCT && $key->type() !== Key::TYPE_NAME_OCT) {
            throw new InvalidArgumentException('Invalid key. Must be of type symmetric');
        }

        if (! $key->has(SymmetricKey::DATA_K)) {
            throw new InvalidArgumentException('Invalid key. The value of the key is missing');
        }
    }
}
