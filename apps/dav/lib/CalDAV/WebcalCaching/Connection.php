<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\WebcalCaching;

use Exception;
use GuzzleHttp\RequestOptions;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\LocalServerException;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Reader;

class Connection {
	public function __construct(
		private IClientService $clientService,
		private IAppConfig $config,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * gets webcal feed from remote server
	 */
	public function queryWebcalFeed(array $subscription): ?string {
		$subscriptionId = $subscription['id'];
		$url = $this->cleanURL($subscription['source']);
		if ($url === null) {
			return null;
		}

		$allowLocalAccess = $this->config->getValueString('dav', 'webcalAllowLocalAccess', 'no');

		$params = [
			'nextcloud' => [
				'allow_local_address' => $allowLocalAccess === 'yes',
			],
			RequestOptions::HEADERS => [
				'User-Agent' => 'Nextcloud Webcal Service',
				'Accept' => 'text/calendar, application/calendar+json, application/calendar+xml',
			],
		];

		$user = parse_url($subscription['source'], PHP_URL_USER);
		$pass = parse_url($subscription['source'], PHP_URL_PASS);
		if ($user !== null && $pass !== null) {
			$params[RequestOptions::AUTH] = [$user, $pass];
		}

		try {
			$client = $this->clientService->newClient();
			$response = $client->get($url, $params);
		} catch (LocalServerException $ex) {
			$this->logger->warning("Subscription $subscriptionId was not refreshed because it violates local access rules", [
				'exception' => $ex,
			]);
			return null;
		} catch (Exception $ex) {
			$this->logger->warning("Subscription $subscriptionId could not be refreshed due to a network error", [
				'exception' => $ex,
			]);
			return null;
		}

		$body = $response->getBody();

		$contentType = $response->getHeader('Content-Type');
		$contentType = explode(';', $contentType, 2)[0];
		switch ($contentType) {
			case 'application/calendar+json':
				try {
					$jCalendar = Reader::readJson($body, Reader::OPTION_FORGIVING);
				} catch (Exception $ex) {
					// In case of a parsing error return null
					$this->logger->warning("Subscription $subscriptionId could not be parsed", ['exception' => $ex]);
					return null;
				}
				return $jCalendar->serialize();

			case 'application/calendar+xml':
				try {
					$xCalendar = Reader::readXML($body);
				} catch (Exception $ex) {
					// In case of a parsing error return null
					$this->logger->warning("Subscription $subscriptionId could not be parsed", ['exception' => $ex]);
					return null;
				}
				return $xCalendar->serialize();

			case 'text/calendar':
			default:
				try {
					$vCalendar = Reader::read($body);
				} catch (Exception $ex) {
					// In case of a parsing error return null
					$this->logger->warning("Subscription $subscriptionId could not be parsed", ['exception' => $ex]);
					return null;
				}
				return $vCalendar->serialize();
		}
	}

	/**
	 * This method will strip authentication information and replace the
	 * 'webcal' or 'webcals' protocol scheme
	 *
	 * @param string $url
	 * @return string|null
	 */
	private function cleanURL(string $url): ?string {
		$parsed = parse_url($url);
		if ($parsed === false) {
			return null;
		}

		if (isset($parsed['scheme']) && $parsed['scheme'] === 'http') {
			$scheme = 'http';
		} else {
			$scheme = 'https';
		}

		$host = $parsed['host'] ?? '';
		$port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
		$path = $parsed['path'] ?? '';
		$query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
		$fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

		$cleanURL = "$scheme://$host$port$path$query$fragment";
		// parse_url is giving some weird results if no url and no :// is given,
		// so let's test the url again
		$parsedClean = parse_url($cleanURL);
		if ($parsedClean === false || !isset($parsedClean['host'])) {
			return null;
		}

		return $cleanURL;
	}
}
