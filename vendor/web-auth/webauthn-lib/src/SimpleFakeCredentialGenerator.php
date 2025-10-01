<?php

declare(strict_types=1);

namespace Webauthn;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use function count;
use function is_int;

final class SimpleFakeCredentialGenerator implements FakeCredentialGenerator
{
    public function __construct(
        private readonly null|CacheItemPoolInterface $cache = null
    ) {
    }

    /**
     * @return PublicKeyCredentialDescriptor[]
     */
    public function generate(Request $request, string $username): array
    {
        if ($this->cache === null) {
            return $this->generateCredentials($username);
        }

        $cacheKey = 'fake_credentials_' . hash('xxh128', $username);
        $cacheItem = $this->cache->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $credentials = $this->generateCredentials($username);
        $cacheItem->set($credentials);
        $this->cache->save($cacheItem);

        return $credentials;
    }

    /**
     * @return PublicKeyCredentialDescriptor[]
     */
    private function generateCredentials(string $username): array
    {
        $transports = [
            PublicKeyCredentialDescriptor::AUTHENTICATOR_TRANSPORT_USB,
            PublicKeyCredentialDescriptor::AUTHENTICATOR_TRANSPORT_NFC,
            PublicKeyCredentialDescriptor::AUTHENTICATOR_TRANSPORT_BLE,
        ];
        $credentials = [];
        for ($i = 0; $i < random_int(1, 3); $i++) {
            $randomTransportKeys = array_rand($transports, random_int(1, count($transports)));
            if (is_int($randomTransportKeys)) {
                $randomTransportKeys = [$randomTransportKeys];
            }
            $randomTransports = array_values(array_intersect_key($transports, array_flip($randomTransportKeys)));
            $credentials[] = PublicKeyCredentialDescriptor::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                hash('sha256', random_bytes(16) . $username),
                $randomTransports
            );
        }

        return $credentials;
    }
}
