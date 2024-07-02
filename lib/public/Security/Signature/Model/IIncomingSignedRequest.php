<?php

declare(strict_types=1);

namespace OCP\Security\Signature\Model;

use OCP\IRequest;

interface IIncomingSignedRequest extends ISignedRequest {
	public function setRequest(IRequest $request): IIncomingSignedRequest;
	public function getRequest(): IRequest;
	public function setTime(int $time): IIncomingSignedRequest;
	public function getTime(): int;
	public function setOrigin(string $origin): IIncomingSignedRequest;
	public function getOrigin(): string;
	/** local address */
	public function setHost(string $host): IIncomingSignedRequest;
	public function getHost(): string;
	public function setEstimatedSignature(string $signature): IIncomingSignedRequest;
	public function getEstimatedSignature(): string;
}
