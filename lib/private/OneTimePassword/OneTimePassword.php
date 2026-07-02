<?php

namespace OC\OneTimePassword;

use OCP\OneTimePassword\IOneTimePassword;
use Override;

class OneTimePassword implements IOneTimePassword {
	private int $id;
	private ?string $password;
	private ?\DateTime $expiration;

	public function __construct(
		private string $providerId,
		private string $recipient
	) { }

	#[Override]
	public function setId(int $id): self {
		$this->id = $id;
		return $this;
	}

	#[Override]
	public function getId(): int {
		return $this->id;
	}

	#[Override]
	public function setPassword(?string $password): self {
		$this->password = $password;
		return $this;
	}

	#[Override]
	public function getPassword(): ?string {
		return $this->password;
	}

	#[Override]
	public function setRecipient(string $recipient): self {
		$this->recipient = $recipient;
		return $this;
	}

	#[Override]
	public function getRecipient(): string {
		return $this->recipient;
	}

	#[Override]
	public function getProviderId(): string {
		return $this->providerId;
	}

	#[Override]
	public function setProviderId(string $provider): self {
		$this->providerId = $provider;
	}

	#[Override]
	public function setExpirationTime(?\DateTime $expiration): self {
		$this->expiration = $expiration;
		return $this;
	}

	#[Override]
	public function getExpirationTime(): ?\DateTime {
		return $this->expiration;
	}

}
