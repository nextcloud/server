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

/**
 * Abstract sync service to sync CalDAV and CardDAV data from federated instances.
 */
abstract class ASyncService {
	public function __construct(
		protected IClientService $clientService,
		protected IConfig $config,
	) {
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
	 * @psalm-return array{response: array<string, array>, token: ?string}
	 */
	protected function requestSyncReport(
		string $absoluteUrl,
		string $userName,
		string $sharedSecret,
		?string $syncToken,
	): array {
		$client = $this->clientService->newClient();

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

		return $this->parseMultiStatus($body);
	}

	protected function download(
		string $absoluteUrl,
		string $userName,
		string $sharedSecret,
	): string {
		$client = $this->clientService->newClient();

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
	 * @psalm-return array{response: array<string, array>, token: ?string}
	 */
	private function parseMultiStatus($body): array {
		$xml = new SabreXmlService();

		/** @var MultiStatus $multiStatus */
		$multiStatus = $xml->expect('{DAV:}multistatus', $body);

		$result = [];
		foreach ($multiStatus->getResponses() as $response) {
			$result[$response->getHref()] = $response->getResponseProperties();
		}

		return ['response' => $result, 'token' => $multiStatus->getSyncToken()];
	}
}
