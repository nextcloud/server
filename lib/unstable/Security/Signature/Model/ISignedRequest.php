<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\Signature\Model;

use NCU\Security\Signature\Exceptions\SignatoryNotFoundException;
use NCU\Security\Signature\Exceptions\SignatureElementNotFoundException;

/**
 * model that store data related to a possible signature.
 * those details will be used:
 * - to confirm authenticity of a signed incoming request
 * - to sign an outgoing request
 *
 * @experimental 31.0.0
 * @since 31.0.0
 */
interface ISignedRequest {
	/**
	 * payload of the request
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getBody(): string;

	/**
	 * checksum of the payload of the request
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getDigest(): string;

	/**
	 * set the list of headers related to the signature of the request
	 *
	 * @param array $elements
	 *
	 * @return ISignedRequest
	 * @since 31.0.0
	 */
	public function setSignatureElements(array $elements): ISignedRequest;

	/**
	 * get the list of elements in the Signature header of the request
	 *
	 * @return array
	 * @since 31.0.0
	 */
	public function getSignatureElements(): array;

	/**
	 * @param string $key
	 *
	 * @return string
	 * @throws SignatureElementNotFoundException
	 * @since 31.0.0
	 */
	public function getSignatureElement(string $key): string;

	/**
	 * store a clear version of the signature
	 *
	 * @param string $clearSignature
	 *
	 * @return ISignedRequest
	 * @since 31.0.0
	 */
	public function setClearSignature(string $clearSignature): ISignedRequest;

	/**
	 * returns the clear version of the signature
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getClearSignature(): string;

	/**
	 * set the signed version of the signature
	 *
	 * @param string $signedSignature
	 * @return ISignedRequest
	 * @since 31.0.0
	 */
	public function setSignedSignature(string $signedSignature): ISignedRequest;

	/**
	 * get the signed version of the signature
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getSignedSignature(): string;

	/**
	 * set the signatory, containing keys and details, related to this request
	 *
	 * @param ISignatory $signatory
	 * @return ISignedRequest
	 * @since 31.0.0
	 */
	public function setSignatory(ISignatory $signatory): ISignedRequest;

	/**
	 * get the signatory, containing keys and details, related to this request
	 *
	 * @return ISignatory
	 * @throws SignatoryNotFoundException
	 * @since 31.0.0
	 */
	public function getSignatory(): ISignatory;

	/**
	 * returns if a signatory related to this request have been found and defined
	 *
	 * @return bool
	 * @since 31.0.0
	 */
	public function hasSignatory(): bool;
}
