<?php

declare(strict_types=1);

namespace OCP\Security\PublicPrivateKeyPairs\Model;

interface IKeyPair {
	public function getApp(): string;
	public function getName(): string;
	public function setPublicKey(string $publicKey): IKeyPair;
	public function getPublicKey(): string;
	public function setPrivateKey(string $privateKey): IKeyPair;
	public function getPrivateKey(): string;
}
