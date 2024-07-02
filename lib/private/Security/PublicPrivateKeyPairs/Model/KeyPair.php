<?php

declare(strict_types=1);

namespace OC\Security\PublicPrivateKeyPairs\Model;

use OCP\Security\PublicPrivateKeyPairs\Model\IKeyPair;

class KeyPair implements IKeyPair {
	private string $publicKey = '';
	private string $privateKey = '';

	public function __construct(
		private readonly string $app,
		private readonly string $name
	) {
	}

	public function getApp(): string {
		return $this->app;
	}

	public function getName(): string {
		return $this->name;
	}

	public function setPublicKey(string $publicKey): self {
		$this->publicKey = $publicKey;
		return $this;
	}

	public function getPublicKey(): string {
		return $this->publicKey;
	}

	public function setPrivateKey(string $privateKey): self {
		$this->privateKey = $privateKey;
		return $this;
	}

	public function getPrivateKey(): string {
		return $this->privateKey;
	}
}
