<?php

declare(strict_types=1);

namespace OCP\Security\Signature\Model;

use OCP\IRequest;

interface IOutgoingSignedRequest extends ISignedRequest {
	public function setHost(string $host): IOutgoingSignedRequest;
	public function getHost(): string;
	public function addHeader(string $key, string|int|float|bool|array $value): IOutgoingSignedRequest;
	public function getHeaders(): array;
	public function setClearSignature(string $estimated): IOutgoingSignedRequest;
	public function getClearSignature(): string;
	public function setAlgorithm(string $algorithm): IOutgoingSignedRequest;
	public function getAlgorithm(): string;
}
