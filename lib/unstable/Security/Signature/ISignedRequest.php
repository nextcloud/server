<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\Signature;

use NCU\Security\Signature\Enum\DigestAlgorithm;
use NCU\Security\Signature\Exceptions\SignatoryNotFoundException;
use NCU\Security\Signature\Exceptions\SignatureElementNotFoundException;
use NCU\Security\Signature\Model\Signatory;

/**
 * model that store data related to a possible signature.
 * those details will be used:
 * - to confirm authenticity of a signed incoming request
 * - to sign an outgoing request
 *
 * This interface must not be implemented in your application:
 * @see IIncomingSignedRequest
 * @see IOutgoingSignedRequest
 *
 * @experimental 31.0.0
 */
interface ISignedRequest {
	/**
	 * payload of the request
	 *
	 * @return string
	 * @experimental 31.0.0
	 */
	public function getBody(): string;

	/**
	 * get algorithm used to generate digest
	 *
	 * @return DigestAlgorithm
	 * @experimental 31.0.0
	 */
	public function getDigestAlgorithm(): DigestAlgorithm;

	/**
	 * checksum of the payload of the request
	 *
	 * @return string
	 * @experimental 31.0.0
	 */
	public function getDigest(): string;

	/**
	 * set the list of headers related to the signature of the request
	 *
	 * @param array $elements
	 *
	 * @return self
	 * @experimental 31.0.0
	 */
	public function setSigningElements(array $elements): self;

	/**
	 * get the list of elements in the Signature header of the request
	 *
	 * @return array
	 * @experimental 31.0.0
	 */
	public function getSigningElements(): array;

	/**
	 * @param string $key
	 *
	 * @return string
	 * @throws SignatureElementNotFoundException
	 * @experimental 31.0.0
	 */
	public function getSigningElement(string $key): string;

	/**
	 * returns data used to generate signature
	 *
	 * @return array
	 * @experimental 31.0.0
	 */
	public function getSignatureData(): array;

	/**
	 * get the signed version of the signature
	 *
	 * @return string
	 * @experimental 31.0.0
	 */
	public function getSignature(): string;

	/**
	 * set the signatory, containing keys and details, related to this request
	 *
	 * @param Signatory $signatory
	 * @return self
	 * @experimental 31.0.0
	 */
	public function setSignatory(Signatory $signatory): self;

	/**
	 * get the signatory, containing keys and details, related to this request
	 *
	 * @return Signatory
	 * @throws SignatoryNotFoundException
	 * @experimental 31.0.0
	 */
	public function getSignatory(): Signatory;

	/**
	 * returns if a signatory related to this request have been found and defined
	 *
	 * @return bool
	 * @experimental 31.0.0
	 */
	public function hasSignatory(): bool;
}
