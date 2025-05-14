<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Service;

use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use Sabre\DAV\Xml\Response\MultiStatus;
use Sabre\DAV\Xml\Service as SabreXmlService;
use Sabre\Xml\ParseException;

/**
 * Abstract sync service to sync CalDAV and CardDAV data from federated instances.
 */
abstract class ASyncService {
	private ?IClient $client = null;

	public function __construct(
		protected IClientService $clientService,
		protected IConfig $config,
	) {
	}

	private function getClient(): IClient {
		if ($this->client === null) {
			$this->client = $this->clientService->newClient();
		}

		return $this->client;
	}

	protected function prepareUri(string $host, string $path): string {
		/*
		 * The trailing slash is important for merging the uris together.
		 *
		 * $host is stored in oc_trusted_servers.url and usually without a trailing slash.
		 *
		 * Example for a report request
		 *
		 * $host = 'https://server.internal/cloud'
		 * $path = 'remote.php/dav/addressbooks/system/system/system'
		 *
		 * Without the trailing slash, the webroot is missing:
		 * https://server.internal/remote.php/dav/addressbooks/system/system/system
		 *
		 * Example for a download request
		 *
		 * $host = 'https://server.internal/cloud'
		 * $path = '/cloud/remote.php/dav/addressbooks/system/system/system/Database:alice.vcf'
		 *
		 * The response from the remote usually contains the webroot already and must be normalized to:
		 * https://server.internal/cloud/remote.php/dav/addressbooks/system/system/system/Database:alice.vcf
		 */
		$host = rtrim($host, '/') . '/';

		$uri = \GuzzleHttp\Psr7\UriResolver::resolve(
			\GuzzleHttp\Psr7\Utils::uriFor($host),
			\GuzzleHttp\Psr7\Utils::uriFor($path)
		);

		return (string)$uri;
	}

	/**
	 * @return array{response: array<string, array<array-key, mixed>>, token: ?string, truncated: bool}
	 */
	protected function requestSyncReport(
		string $absoluteUrl,
		string $userName,
		string $sharedSecret,
		?string $syncToken,
	): array {
		$client = $this->getClient();

		$options = [
			'auth' => [$userName, $sharedSecret],
			'body' => $this->buildSyncCollectionRequestBody($syncToken),
			'headers' => ['Content-Type' => 'application/xml'],
			'timeout' => $this->config->getSystemValueInt(
				'carddav_sync_request_timeout',
				IClient::DEFAULT_REQUEST_TIMEOUT,
			),
			'verify' => !$this->config->getSystemValue(
				'sharing.federation.allowSelfSignedCertificates',
				false,
			),
		];

		$response = $client->request(
			'REPORT',
			$absoluteUrl,
			$options,
		);

		$body = $response->getBody();
		assert(is_string($body));

		return $this->parseMultiStatus($body, $absoluteUrl);
	}

	protected function download(
		string $absoluteUrl,
		string $userName,
		string $sharedSecret,
	): string {
		$client = $this->getClient();

		$options = [
			'auth' => [$userName, $sharedSecret],
			'verify' => !$this->config->getSystemValue(
				'sharing.federation.allowSelfSignedCertificates',
				false,
			),
		];

		$response = $client->get(
			$absoluteUrl,
			$options,
		);

		return (string)$response->getBody();
	}

	private function buildSyncCollectionRequestBody(?string $syncToken): string {
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$root = $dom->createElementNS('DAV:', 'd:sync-collection');
		$sync = $dom->createElement('d:sync-token', $syncToken ?? '');
		$prop = $dom->createElement('d:prop');
		$cont = $dom->createElement('d:getcontenttype');
		$etag = $dom->createElement('d:getetag');

		$prop->appendChild($cont);
		$prop->appendChild($etag);
		$root->appendChild($sync);
		$root->appendChild($prop);
		$dom->appendChild($root);
		return $dom->saveXML();
	}

	/**
	 * @return array{response: array<string, array<array-key, mixed>>, token: ?string, truncated: bool}
	 * @throws ParseException
	 */
	private function parseMultiStatus(string $body, string $resourceUrl): array {
		/** @var MultiStatus $multiStatus */
		$multiStatus = (new SabreXmlService())->expect('{DAV:}multistatus', $body);

		$result = [];
		$truncated = false;

		foreach ($multiStatus->getResponses() as $response) {
			$href = $response->getHref();
			if ($response->getHttpStatus() === '507' && $this->isResponseForRequestUri($href, $resourceUrl)) {
				$truncated = true;
			} else {
				$result[$response->getHref()] = $response->getResponseProperties();
			}
		}

		return ['response' => $result, 'token' => $multiStatus->getSyncToken(), 'truncated' => $truncated];
	}

	/**
	 * Determines whether the provided response URI corresponds to the given request URI.
	 */
	private function isResponseForRequestUri(string $responseUri, string $requestUri): bool {
		/*
		 * Example response uri:
		 *
		 * /remote.php/dav/addressbooks/system/system/system/
		 * /cloud/remote.php/dav/addressbooks/system/system/system/ (when installed in a subdirectory)
		 *
		 * Example request uri:
		 *
		 * https://foo.bar/remote.php/dav/addressbooks/system/system/system
		 *
		 * References:
		 * https://github.com/nextcloud/3rdparty/blob/e0a509739b13820f0a62ff9cad5d0fede00e76ee/sabre/dav/lib/DAV/Sync/Plugin.php#L172-L174
		 * https://github.com/nextcloud/server/blob/b40acb34a39592070d8455eb91c5364c07928c50/apps/federation/lib/SyncFederationAddressBooks.php#L41
		 */
		return str_ends_with(
			rtrim($requestUri, '/'),
			rtrim($responseUri, '/'),
		);
	}
}
