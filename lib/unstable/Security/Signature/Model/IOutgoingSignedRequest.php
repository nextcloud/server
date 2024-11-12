<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\Signature\Model;

use NCU\Security\Signature\ISignatureManager;

/**
 * extends ISignedRequest to add info requested at the generation of the signature
 *
 * @see ISignatureManager for details on signature
 * @experimental 31.0.0
 * @since 31.0.0
 */
interface IOutgoingSignedRequest extends ISignedRequest {
	/**
	 * set the host of the recipient of the request.
	 *
	 * @param string $host
	 * @return IOutgoingSignedRequest
	 * @since 31.0.0
	 */
	public function setHost(string $host): IOutgoingSignedRequest;

	/**
	 * get the host of the recipient of the request.
	 * - on incoming request, this is the local hostname of current instance.
	 * - on outgoing request, this is the remote instance.
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getHost(): string;

	/**
	 * add a key/value pair to the headers of the request
	 *
	 * @param string $key
	 * @param string|int|float|bool|array $value
	 *
	 * @return IOutgoingSignedRequest
	 * @since 31.0.0
	 */
	public function addHeader(string $key, string|int|float|bool|array $value): IOutgoingSignedRequest;

	/**
	 * returns list of headers value that will be added to the base request
	 *
	 * @return array
	 * @since 31.0.0
	 */
	public function getHeaders(): array;

	/**
	 * store a clear version of the signature
	 *
	 * @param string $estimated
	 *
	 * @return IOutgoingSignedRequest
	 * @since 31.0.0
	 */
	public function setClearSignature(string $estimated): IOutgoingSignedRequest;

	/**
	 * returns the clear version of the signature
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getClearSignature(): string;

	/**
	 * set algorithm to be used to sign the signature
	 *
	 * @param string $algorithm
	 *
	 * @return IOutgoingSignedRequest
	 * @since 31.0.0
	 */
	public function setAlgorithm(string $algorithm): IOutgoingSignedRequest;

	/**
	 * returns the algorithm set to sign the signature
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getAlgorithm(): string;
}
