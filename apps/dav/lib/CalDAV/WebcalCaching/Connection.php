<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\WebcalCaching;

use Exception;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\LocalServerException;
use OCP\IAppConfig;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Xml\Property\Href;
use Sabre\VObject\Reader;

class Connection {
	public function __construct(private IClientService $clientService,
		private IAppConfig $config,
		private LoggerInterface $logger) {
	}

	/**
	 * gets webcal feed from remote server
	 */
	public function queryWebcalFeed(array $subscription, array &$mutations): ?string {
		$client = $this->clientService->newClient();

		$didBreak301Chain = false;
		$latestLocation = null;

		$handlerStack = HandlerStack::create();
		$handlerStack->push(Middleware::mapRequest(function (RequestInterface $request) {
			return $request
				->withHeader('Accept', 'text/calendar, application/calendar+json, application/calendar+xml')
				->withHeader('User-Agent', 'Nextcloud Webcal Service');
		}));
		$handlerStack->push(Middleware::mapResponse(function (ResponseInterface $response) use (&$didBreak301Chain, &$latestLocation) {
			if (!$didBreak301Chain) {
				if ($response->getStatusCode() !== 301) {
					$didBreak301Chain = true;
				} else {
					$latestLocation = $response->getHeader('Location');
				}
			}
			return $response;
		}));

		$allowLocalAccess = $this->config->getValueString('dav', 'webcalAllowLocalAccess', 'no');
		$subscriptionId = $subscription['id'];
		$url = $this->cleanURL($subscription['source']);
		if ($url === null) {
			return null;
		}

		try {
			$params = [
				'allow_redirects' => [
					'redirects' => 10
				],
				'handler' => $handlerStack,
				'nextcloud' => [
					'allow_local_address' => $allowLocalAccess === 'yes',
				]
			];

			$user = parse_url($subscription['source'], PHP_URL_USER);
			$pass = parse_url($subscription['source'], PHP_URL_PASS);
			if ($user !== null && $pass !== null) {
				$params['auth'] = [$user, $pass];
			}

			$response = $client->get($url, $params);
			$body = $response->getBody();

			if ($latestLocation !== null) {
				$mutations['{http://calendarserver.org/ns/}source'] = new Href($latestLocation);
			}

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
