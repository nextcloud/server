<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OCM;

use OCP\AppFramework\Attribute\Consumable;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IResponse;
use OCP\OCM\Events\LocalOCMDiscoveryEvent;
use OCP\OCM\Exceptions\OCMCapabilityException;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\OCM\Exceptions\OCMRequestException;
use OCP\Security\Signature\Exceptions\IncomingRequestException;
use OCP\Security\Signature\Exceptions\SignatureException;
use OCP\Security\Signature\IIncomingSignedRequest;

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
	 * @param string|null $ocmAddress for RFC 9421, the sender's OCM address
	 *                                (the keyid is opaque)
	 *
	 * @return IIncomingSignedRequest|null null if remote does not (and never did) support signed request
	 * @throws IncomingRequestException
	 * @since 33.0.0
	 */
	public function getIncomingSignedRequest(?string $ocmAddress = null): ?IIncomingSignedRequest;

	/**
	 * Confirm that the host portion of $ocmAddress matches $signedOrigin
	 * under the current local signing policy.
	 *
	 * @param string|null $signedOrigin verified origin of the signed request,
	 *                                  typically taken from {@see IIncomingSignedRequest::getOrigin()} or
	 *                                  from {@see \OCP\OCM\Events\OCMEndpointRequestEvent::getRemote()}.
	 *                                  NULL if the request was not signed.
	 * @param string $ocmAddress in `user@host` or `user@https://host` form
	 *
	 * @throws IncomingRequestException on mismatch or malformed address
	 * @since 35.0.0
	 */
	public function confirmRequestOrigin(?string $signedOrigin, string $ocmAddress): void;

	/**
	 * Create an RFC 9421 signed OCM response bound to an incoming request.
	 *
	 * The returned response renders the exact bytes supplied in `$body`.
	 * The signature is computed when the response is sent, so the status
	 * and headers of the returned response may still be adjusted.
	 *
	 * @template S of Http::STATUS_*
	 *
	 * @param IIncomingSignedRequest $request verified incoming OCM request
	 * @param string $body exact response body to sign and return
	 * @param S $status response status code, covered by the signature
	 * @param string $contentType value of the signed Content-Type header
	 * @return Response<S, array<string, mixed>>
	 *
	 * @throws IncomingRequestException if the request is not an RFC 9421 signed request
	 * @throws SignatureException if the response cannot be signed
	 * @since 36.0.0
	 */
	public function createSignedResponse(
		IIncomingSignedRequest $request,
		string $body,
		int $status = Http::STATUS_OK,
		string $contentType = 'application/json; charset=utf-8',
	): Response;

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
	 * @param string|null $signatureNonce nonce for a request-bound signed exchange
	 *
	 * @throws OCMProviderException
	 * @throws OCMCapabilityException if remote does not support $capability
	 * @throws OCMRequestException
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
		?string $signatureNonce = null,
	): IResponse;
}
