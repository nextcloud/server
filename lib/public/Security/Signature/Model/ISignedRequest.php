<?php

declare(strict_types=1);

namespace OCP\Security\Signature\Model;

use OCP\Security\PublicPrivateKeyPairs\Model\IKeyPair;

interface ISignedRequest {
	public function getBody(): string;
	public function getDigest(): string;
	public function setSignatureHeader(array $signatureHeader): ISignedRequest;
	public function getSignatureHeader(): array;
	public function setSignedSignature(string $signedSignature): ISignedRequest;
	public function getSignedSignature(): string;
	public function setSignatory(ISignatory $signatory): ISignedRequest;
	public function getSignatory(): ISignatory;
	public function hasSignatory(): bool;
}
