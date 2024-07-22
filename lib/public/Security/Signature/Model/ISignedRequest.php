<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Security\Signature\Model;

/**
 * model that store data related to a possible signature.
 * those details will be used:
 * - to confirm authenticity of a signed incoming request
 * - to sign an outgoing request
 *
 * @since 30.0.0
 */
interface ISignedRequest {
	/**
	 * payload of the request
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getBody(): string;

	/**
	 * checksum of the payload of the request
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getDigest(): string;

	/**
	 * set the list of headers related to the signature of the request
	 *
	 * @param array $signatureHeader
	 * @return ISignedRequest
	 * @since 30.0.0
	 */
	public function setSignatureHeader(array $signatureHeader): ISignedRequest;

	/**
	 * get the list of headers related to the signature of the request
	 *
	 * @return array
	 * @since 30.0.0
	 */
	public function getSignatureHeader(): array;

	/**
	 * set the signed version of the signature
	 *
	 * @param string $signedSignature
	 * @return ISignedRequest
	 * @since 30.0.0
	 */
	public function setSignedSignature(string $signedSignature): ISignedRequest;

	/**
	 * get the signed version of the signature
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getSignedSignature(): string;

	/**
	 * set the signatory, containing keys and details, related to this request
	 *
	 * @param ISignatory $signatory
	 * @return ISignedRequest
	 * @since 30.0.0
	 */
	public function setSignatory(ISignatory $signatory): ISignedRequest;

	/**
	 * get the signatory, containing keys and details, related to this request
	 *
	 * @return ISignatory
	 * @since 30.0.0
	 */
	public function getSignatory(): ISignatory;

	/**
	 * returns if a signatory related to this request have been found and defined
	 *
	 * @return bool
	 * @since 30.0.0
	 */
	public function hasSignatory(): bool;
}
