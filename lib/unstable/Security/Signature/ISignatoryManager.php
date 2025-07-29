<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\Signature;

use NCU\Security\Signature\Model\Signatory;

/**
 * ISignatoryManager contains a group of method that will help
 *   - signing outgoing request
 *   - confirm the authenticity of incoming signed request.
 *
 * This interface must be implemented to generate a `SignatoryManager` to
 *  be used with {@see ISignatureManager}
 *
 * @experimental 31.0.0
 */
interface ISignatoryManager {
	/**
	 * id of the signatory manager.
	 * This is used to store, confirm uniqueness and avoid conflict of the remote key pairs.
	 *
	 * Must be unique.
	 *
	 * @return string
	 * @experimental 31.0.0
	 */
	public function getProviderId(): string;

	/**
	 * options that might affect the way the whole process is handled:
	 * [
	 *   'bodyMaxSize' => 10000,
	 *   'ttl' => 300,
	 *   'ttlSignatory' => 86400*3,
	 *   'extraSignatureHeaders' => [],
	 *   'algorithm' => 'sha256',
	 *   'dateHeader' => "D, d M Y H:i:s T",
	 * ]
	 *
	 * @return array
	 * @experimental 31.0.0
	 */
	public function getOptions(): array;

	/**
	 * generate and returns local signatory including private and public key pair.
	 *
	 * Used to sign outgoing request
	 *
	 * @return Signatory
	 * @experimental 31.0.0
	 */
	public function getLocalSignatory(): Signatory;

	/**
	 * retrieve details and generate signatory from remote instance.
	 * If signatory cannot be found, returns NULL.
	 *
	 * Used to confirm authenticity of incoming request.
	 *
	 * @param string $remote
	 *
	 * @return Signatory|null must be NULL if no signatory is found
	 * @experimental 31.0.0
	 */
	public function getRemoteSignatory(string $remote): ?Signatory;
}
