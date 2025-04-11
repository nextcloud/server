<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\Signature;

use NCU\Security\Signature\Enum\SignatureAlgorithm;
use NCU\Security\Signature\Exceptions\SignatoryException;
use NCU\Security\Signature\Exceptions\SignatoryNotFoundException;

/**
 * extends ISignedRequest to add info requested at the generation of the signature
 *
 *  This interface must not be implemented in your application but
 *  instead obtained from {@see ISignatureManager::getIncomingSignedRequest}.
 *
 *   ```php
 *   $signedRequest = $this->signatureManager->getIncomingSignedRequest($mySignatoryManager);
 *   ```
 *
 * @see ISignatureManager for details on signature
 * @experimental 31.0.0
 */
interface IOutgoingSignedRequest extends ISignedRequest {
	/**
	 * set the host of the recipient of the request.
	 *
	 * @param string $host
	 * @return self
	 * @experimental 31.0.0
	 */
	public function setHost(string $host): self;

	/**
	 * get the host of the recipient of the request.
	 * - on incoming request, this is the local hostname of current instance.
	 * - on outgoing request, this is the remote instance.
	 *
	 * @return string
	 * @experimental 31.0.0
	 */
	public function getHost(): string;

	/**
	 * add a key/value pair to the headers of the request
	 *
	 * @param string $key
	 * @param string|int|float $value
	 *
	 * @return self
	 * @experimental 31.0.0
	 */
	public function addHeader(string $key, string|int|float $value): self;

	/**
	 * returns list of headers value that will be added to the base request
	 *
	 * @return array
	 * @experimental 31.0.0
	 */
	public function getHeaders(): array;

	/**
	 * set the ordered list of used headers in the Signature
	 *
	 * @param list<string> $list
	 *
	 * @return self
	 * @experimental 31.0.0
	 */
	public function setHeaderList(array $list): self;

	/**
	 * returns ordered list of used headers in the Signature
	 *
	 * @return list<string>
	 * @experimental 31.0.0
	 */
	public function getHeaderList(): array;

	/**
	 * set algorithm to be used to sign the signature
	 *
	 * @param SignatureAlgorithm $algorithm
	 *
	 * @return self
	 * @experimental 31.0.0
	 */
	public function setAlgorithm(SignatureAlgorithm $algorithm): self;

	/**
	 * returns the algorithm set to sign the signature
	 *
	 * @return SignatureAlgorithm
	 * @experimental 31.0.0
	 */
	public function getAlgorithm(): SignatureAlgorithm;

	/**
	 * sign outgoing request providing a certificate that it emanate from this instance
	 *
	 * @return self
	 * @throws SignatoryException
	 * @throws SignatoryNotFoundException
	 * @experimental 31.0.0
	 */
	public function sign(): self;
}
