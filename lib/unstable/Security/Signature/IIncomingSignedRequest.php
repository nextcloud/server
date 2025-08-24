<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\Signature;

use NCU\Security\Signature\Exceptions\SignatoryNotFoundException;
use NCU\Security\Signature\Exceptions\SignatureElementNotFoundException;
use NCU\Security\Signature\Exceptions\SignatureException;
use OCP\IRequest;

/**
 * model wrapping an actual incoming request, adding details about the signature and the
 * authenticity of the origin of the request.
 *
 * This interface must not be implemented in your application but
 * instead obtained from {@see ISignatureManager::getIncomingSignedRequest}.
 *
 *  ```php
 *  $signedRequest = $this->signatureManager->getIncomingSignedRequest($mySignatoryManager);
 *  ```
 *
 * @see ISignatureManager for details on signature
 * @experimental 31.0.0
 */
interface IIncomingSignedRequest extends ISignedRequest {
	/**
	 * returns the base IRequest
	 *
	 * @return IRequest
	 * @experimental 31.0.0
	 */
	public function getRequest(): IRequest;

	/**
	 * get the hostname at the source of the base request.
	 * based on the keyId defined in the signature header.
	 *
	 * @return string
	 * @experimental 31.0.0
	 */
	public function getOrigin(): string;

	/**
	 * returns the keyId extracted from the signature headers.
	 * keyId is a mandatory entry in the headers of a signed request.
	 *
	 * @return string
	 * @throws SignatureElementNotFoundException
	 * @experimental 31.0.0
	 */
	public function getKeyId(): string;

	/**
	 * confirm the current signed request's identity is correct
	 *
	 * @throws SignatureException
	 * @throws SignatoryNotFoundException
	 * @experimental 31.0.0
	 */
	public function verify(): void;
}
