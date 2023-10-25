<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, Thomas Citharel
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Nominatim;

use Exception;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\Location\ILocationAddress;
use OCP\Location\ILocationProvider;
use OCP\Location\LocationAddress;
use OCP\Util;
use Psr\Log\LoggerInterface;

class LocationProvider implements ILocationProvider {

	private IClient $client;
	private string $version;
	private ICache $cache;

	public function __construct(private LoggerInterface $logger, IClientService $clientService, ICacheFactory $cacheFactory) {
		$this->client = $clientService->newClient();
		$this->version = implode('.', Util::getVersion());
		$this->cache = $cacheFactory->createDistributed('nominatim');
	}

	/**
	 * @throws Exception
	 */
	public function geocode(float $longitude, float $latitude, array $options = []): array {

			$params = [
				'lat' => number_format($latitude, 5),
				'lon' => number_format($longitude, 5),
				'addressdetails' => 1,
				'format' => 'json',
			];
			$url = 'https://nominatim.openstreetmap.org/reverse';
			$result = $this->requestJSON($url, $params);
			return [$this->formatOsmAddress($result)];
	}

	/**
	 * @throws Exception
	 */
	public function search(string $address, array $options = []): array {
		$params = [
			'q' => $address,
			'format' => 'json',
			'addressdetails' => '1',
			'extratags' => '1',
			'namedetails' => '1',
			'limit' => '1',
		];
		$url = 'https://nominatim.openstreetmap.org/search';
		$results = $this->requestJSON($url, $params);
		return array_map(fn ($result) => $this->formatOsmAddress($result), $results);
	}

	private function formatOsmAddress(array $json): ILocationAddress {
		$address = new LocationAddress();
		if (isset($json['address'])) {
			$jsonAddr = $json['address'];
			$address->setStreetName($jsonAddr['road']);
			$cityAddress = '';
			// priority : city, town, village, municipality
			if (isset($jsonAddr['city'])) {
				$cityAddress = $jsonAddr['city'];
			} elseif (isset($jsonAddr['town'])) {
				$cityAddress = $jsonAddr['town'];
			} elseif (isset($jsonAddr['village'])) {
				$cityAddress = $jsonAddr['village'];
			} elseif (isset($jsonAddr['municipality'])) {
				$cityAddress = $jsonAddr['municipality'];
			}
			$address->setLocality($cityAddress);
			// post code
			if (isset($jsonAddr['postcode'])) {
				$address->setPostalCode($jsonAddr['postcode']);
			}
			if (isset($jsonAddr['state'])) {
				$address->setRegion($jsonAddr['state']);
			} elseif (isset($jsonAddr['region'])) {
				$address->setRegion($jsonAddr['region']);
			}
			// country
			if (isset($jsonAddr['country'])) {
				$address->setCountry($jsonAddr['country']);
			}
		}
		if (isset($json['osm_id'])) {
			$address->setOriginId((string)$json['osm_id']);
		}
		if (isset($json['type'])) {
			$address->setType($json['type']);
		}
		if (isset($json['display_name'])) {
			$address->setDescription($json['display_name']);
		}
		return $address;
	}

	/**
	 * Make a HTTP GET request and parse JSON result.
	 * Request results are cached until the 'Expires' response header says so
	 *
	 * @param string $url Base URL to query
	 * @param array $params GET parameters
	 * @return array which contains the error message or the parsed JSON result
	 * @throws Exception
	 */
	private function requestJSON(string $url, array $params = []): array {
		$cacheKey = $url . '|' . implode(',', $params) . '|' . implode(',', array_keys($params));
		$cacheValue = $this->cache->get($cacheKey);
		if ($cacheValue !== null) {
			return $cacheValue;
		}

		$options = [
			'headers' => [
				'User-Agent' => 'Nextcloud/' . $this->version . ' nextcloud.com'
			],
		];

		$reqUrl = $url;
		if (count($params) > 0) {
			$paramsContent = http_build_query($params);
			$reqUrl = $url . '?' . $paramsContent;
		}
		$this->logger->debug('Requesting Nominatim with URL ' . $reqUrl);
		$response = $this->client->get($reqUrl, $options);
		$body = $response->getBody();
		$headers = $response->getHeaders();
		$respCode = $response->getStatusCode();

		if ($respCode >= 400) {
			throw new \RuntimeException();
		} else {
			$json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

			// default cache duration is one hour
			$cacheDuration = 60 * 60;
			if (isset($headers['Expires']) && count($headers['Expires']) > 0) {
				// if the Expires response header is set, use it to define cache duration
				$expireTs = (new \DateTime($headers['Expires'][0]))->getTimestamp();
				$nowTs = (new \DateTime())->getTimestamp();
				$duration = $expireTs - $nowTs;
				if ($duration > $cacheDuration) {
					$cacheDuration = $duration;
				}
			}
			$this->cache->set($cacheKey, $json, $cacheDuration);

			return $json;
		}
	}
}
