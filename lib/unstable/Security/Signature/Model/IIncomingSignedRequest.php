<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\Signature\Model;

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
	 * set the core IRequest that might be signed
	 *
	 * @param IRequest $request
	 * @return IIncomingSignedRequest
	 * @since 31.0.0
	 */
	public function setRequest(IRequest $request): IIncomingSignedRequest;

	/**
	 * returns the base IRequest
	 *
	 * @return IRequest
	 * @since 31.0.0
	 */
	public function getRequest(): IRequest;

	/**
	 * set the time, extracted from the base request headers
	 *
	 * @param int $time
	 * @return IIncomingSignedRequest
	 * @since 31.0.0
	 */
	public function setTime(int $time): IIncomingSignedRequest;

	/**
	 * get the time, extracted from the base request headers
	 *
	 * @return int
	 * @since 31.0.0
	 */
	public function getTime(): int;

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
	 * @since 31.0.0
	 */
	public function getKeyId(): string;

	/**
	 * store a clear and estimated version of the signature, based on payload and headers.
	 * This clear version will be compared with the real signature using
	 * the public key of remote instance at the origin of the request.
	 *
	 * @param string $signature
	 * @return IIncomingSignedRequest
	 * @since 31.0.0
	 */
	public function setEstimatedSignature(string $signature): IIncomingSignedRequest;

	/**
	 * returns a clear and estimated version of the signature, based on payload and headers.
	 * This clear version will be compared with the real signature using
	 * the public key of remote instance at the origin of the request.
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getEstimatedSignature(): string;
}
