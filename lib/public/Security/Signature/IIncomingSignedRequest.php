<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Security\Signature;

use OCP\AppFramework\Attribute\Consumable;
use OCP\IRequest;
use OCP\Security\Signature\Exceptions\SignatoryNotFoundException;
use OCP\Security\Signature\Exceptions\SignatureElementNotFoundException;
use OCP\Security\Signature\Exceptions\SignatureException;

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
 * @since 33.0.0
 */
#[Consumable(since: '33.0.0')]
interface IIncomingSignedRequest extends ISignedRequest {
	/**
	 * returns the base IRequest
	 *
	 * @return IRequest
	 * @since 33.0.0
	 */
	public function getRequest(): IRequest;

	/**
	 * get the hostname at the source of the base request.
	 * based on the keyId defined in the signature header.
	 *
	 * @return string
	 * @since 33.0.0
	 */
	public function getOrigin(): string;

	/**
	 * returns the keyId extracted from the signature headers.
	 * keyId is a mandatory entry in the headers of a signed request.
	 *
	 * @return string
	 * @throws SignatureElementNotFoundException
	 * @since 33.0.0
	 */
	public function getKeyId(): string;

	/**
	 * confirm the current signed request's identity is correct
	 *
	 * @throws SignatureException
	 * @throws SignatoryNotFoundException
	 * @since 33.0.0
	 */
	public function verify(): void;
}
