<?php

declare(strict_types=1);

namespace OCP\Security\Signature\Model;

interface ISignatory {
	public function getKeyId(): string;
	public function getPublicKey(): string;
	public function getPrivateKey(): string;
	public function setAccount(string $account): self;
	public function getAccount(): string;
	public function setMetadata(array $metadata): self;
	public function getMetadata(): array;
	public function setMetaValue(string $key, string|int $value): self;
	public function setType(SignatoryType $type): self;
	public function getType(): SignatoryType;
	public function setStatus(SignatoryStatus $status): self;
	public function getStatus(): SignatoryStatus;
	public function getLastUpdated(): int;
}
