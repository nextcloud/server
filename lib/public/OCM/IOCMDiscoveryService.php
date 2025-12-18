<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OCM;

use NCU\Security\Signature\Exceptions\IncomingRequestException;
use NCU\Security\Signature\IIncomingSignedRequest;
use OCP\AppFramework\Attribute\Consumable;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IResponse;
use OCP\OCM\Events\LocalOCMDiscoveryEvent;
use OCP\OCM\Exceptions\OCMCapabilityException;
use OCP\OCM\Exceptions\OCMProviderException;

/**
 * Discover remote OCM services
 *
 * @since 28.0.0
 */
#[Consumable(since: '28.0.0')]
interface IOCMDiscoveryService {
	/**
	 * Discover remote OCM services
	 *
	 * @param string $remote address of the remote provider
	 * @param bool $skipCache ignore cache, refresh data
	 *
	 * @return IOCMProvider
	 * @throws OCMProviderException if no valid discovery data can be returned
	 * @since 28.0.0
	 * @since 32.0.0 returns ICapabilityAwareOCMProvider instead of IOCMProvider
	 * @since 33.0.0 returns IOCMProvider (rollback)
	 */
	public function discover(string $remote, bool $skipCache = false): IOCMProvider;

	/**
	 * return discovery data about local instance.
	 *
	 * will generate event {@see LocalOCMDiscoveryEvent} so that 3rd parties can define new resources.
	 *
	 * @param bool $fullDetails complete details, including public keys.
	 *                          Set to FALSE for client (capabilities) purpose.
	 *
	 * @return IOCMProvider
	 * @since 33.0.0
	 */
	public function getLocalOCMProvider(bool $fullDetails = true): IOCMProvider;

	/**
	 * returns signed request if available.
	 *
	 * throw an exception:
	 * - if request is signed, but wrongly signed
	 * - if request is not signed but instance is configured to only accept signed ocm request
	 *
	 * @return IIncomingSignedRequest|null null if remote does not (and never did) support signed request
	 * @throws IncomingRequestException
	 * @since 33.0.0
	 */
	public function getIncomingSignedRequest(): ?IIncomingSignedRequest;

	/**
	 * Request a remote OCM endpoint.
	 *
	 * Capability can be filtered out
	 * The final path will be generated based on remote discovery.
	 *
	 * @param string|null $capability when not NULL, method will throw
	 *                                {@see OCMCapabilityException}
	 *                                if remote does not support the capability
	 * @param string $remote remote ocm cloud id
	 * @param string $ocmSubPath path to reach, complementing the ocm endpoint extracted
	 *                           from remote discovery data
	 * @param array|null $payload payload attached to the request
	 * @param string $method method to use ('get', 'post', 'put', 'delete')
	 * @param IClient|null $client NULL to use default {@see IClient}
	 * @param array|null $options options related to IClient
	 * @param bool $signed FALSE to not auth the request
	 *
	 * @throws OCMProviderException
	 * @throws OCMCapabilityException if remote does not support $capability
	 * @since 33.0.0
	 */
	public function requestRemoteOcmEndpoint(
		?string $capability,
		string $remote,
		string $ocmSubPath,
		?array $payload = null,
		string $method = 'get',
		?IClient $client = null,
		?array $options = null,
		bool $signed = true,
	): IResponse;
}
