<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\Signature;

use NCU\Security\Signature\Exceptions\SignatoryNotFoundException;
use NCU\Security\Signature\Exceptions\SignatureElementNotFoundException;
use NCU\Security\Signature\Model\Signatory;

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
	 * @return self
	 * @since 31.0.0
	 */
	public function setSigningElements(array $elements): self;

	/**
	 * get the list of elements in the Signature header of the request
	 *
	 * @return array
	 * @since 31.0.0
	 */
	public function getSigningElements(): array;

	/**
	 * @param string $key
	 *
	 * @return string
	 * @throws SignatureElementNotFoundException
	 * @since 31.0.0
	 */
	public function getSigningElement(string $key): string;

	/**
	 * store data used to generate signature
	 *
	 * @param array $data
	 *
	 * @return self
	 * @since 31.0.0
	 */
	public function setSignatureData(array $data): self;

	/**
	 * returns data used to generate signature
	 *
	 * @return array
	 * @since 31.0.0
	 */
	public function getSignatureData(): array;

	/**
	 * set the signed version of the signature
	 *
	 * @param string $signature
	 *
	 * @return self
	 * @since 31.0.0
	 */
	public function setSignature(string $signature): self;

	/**
	 * get the signed version of the signature
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getSignature(): string;

	/**
	 * set the signatory, containing keys and details, related to this request
	 *
	 * @param Signatory $signatory
	 * @return self
	 * @since 31.0.0
	 */
	public function setSignatory(Signatory $signatory): self;

	/**
	 * get the signatory, containing keys and details, related to this request
	 *
	 * @return Signatory
	 * @throws SignatoryNotFoundException
	 * @since 31.0.0
	 */
	public function getSignatory(): Signatory;

	/**
	 * returns if a signatory related to this request have been found and defined
	 *
	 * @return bool
	 * @since 31.0.0
	 */
	public function hasSignatory(): bool;
}
