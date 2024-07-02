<?php

declare(strict_types=1);

namespace OCP\Security\Signature;

use OCP\Security\Signature\Model\IIncomingSignedRequest;
use OCP\Security\Signature\Model\ISignatory;

interface ISignatoryManager {
	public function getProviderId(): string;
	public function getOptions(): array;
	public function getLocalSignatory(): ISignatory;

	/**
	 * @return ISignatory|null must be NULL if no signatory is found
	 */
	public function getRemoteSignatory(IIncomingSignedRequest $signedRequest): ?ISignatory;
}
