<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Security\Signature;

use OCP\Security\Signature\Model\IIncomingSignedRequest;
use OCP\Security\Signature\Model\ISignatory;

/**
 * ISignatoryManager contains a group of method that will help
 *   - signing outgoing request
 *   - confirm the authenticity of incoming signed request.
 *
 * @since 30.0.0
 */
interface ISignatoryManager {
	/**
	 * id of the signatory manager.
	 * This is used to store, confirm uniqueness and avoid conflict of the remote key pairs.
	 *
	 * Must be unique.
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getProviderId(): string;

	/**
	 * options that might affect the way the whole process is handled:
	 * [
	 *   'ttl' => 300,
	 *   'ttlSignatory' => 86400*3,
	 *   'extraSignatureHeaders' => [],
	 *   'algorithm' => 'sha256',
	 *   'dateHeader' => "D, d M Y H:i:s T",
	 * ]
	 *
	 * @return array
	 * @since 30.0.0
	 */
	public function getOptions(): array;

	/**
	 * generate and returns local signatory including private and public key pair.
	 *
	 * Used to sign outgoing request
	 *
	 * @return ISignatory
	 * @since 30.0.0
	 */
	public function getLocalSignatory(): ISignatory;

	/**
	 * retrieve details and generate signatory from remote instance.
	 * If signatory cannot be found, returns NULL.
	 *
	 * Used to confirm authenticity of incoming request.
	 *
	 * @param IIncomingSignedRequest $signedRequest
	 *
	 * @return ISignatory|null must be NULL if no signatory is found
	 * @since 30.0.0
	 */
	public function getRemoteSignatory(IIncomingSignedRequest $signedRequest): ?ISignatory;
}
