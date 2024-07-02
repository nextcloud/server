<?php

declare(strict_types=1);

namespace OCP\Security\PublicPrivateKeyPairs;

use OCP\Security\PublicPrivateKeyPairs\Model\IKeyPair;

interface IKeyPairManager {
	public function getKeyPair(string $app, string $name, array $options = []): IKeyPair;
	public function deleteKeyPair(string $app, string $name): void;
	public function testKeyPair(IKeyPair $keyPair): bool;
}
