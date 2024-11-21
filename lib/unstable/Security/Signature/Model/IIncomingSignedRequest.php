<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\Signature\Model;

use NCU\Security\Signature\Exceptions\SignatureElementNotFoundException;
use NCU\Security\Signature\ISignatureManager;
use OCP\IRequest;

/**
 * model wrapping an actual incoming request, adding details about the signature and the
 * authenticity of the origin of the request.
 *
 * @see ISignatureManager for details on signature
 * @experimental 31.0.0
 * @since 31.0.0
 */
interface IIncomingSignedRequest extends ISignedRequest {
	/**
	 * returns the base IRequest
	 *
	 * @return IRequest
	 * @since 31.0.0
	 */
	public function getRequest(): IRequest;

	/**
	 * set the hostname at the source of the request,
	 * based on the keyId defined in the signature header.
	 *
	 * @param string $origin
	 * @return IIncomingSignedRequest
	 * @since 31.0.0
	 */
	public function setOrigin(string $origin): IIncomingSignedRequest;

	/**
	 * get the hostname at the source of the base request.
	 * based on the keyId defined in the signature header.
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getOrigin(): string;

	/**
	 * returns the keyId extracted from the signature headers.
	 * keyId is a mandatory entry in the headers of a signed request.
	 *
	 * @return string
	 * @throws SignatureElementNotFoundException
	 * @since 31.0.0
	 */
	public function getKeyId(): string;
}
