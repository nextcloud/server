<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Julien Veyssier
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0-or-later
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
namespace OCA\WeatherStatus\Service;

use OCA\WeatherStatus\AppInfo\Application;
use OCA\WeatherStatus\ResponseDefinitions;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\App\IAppManager;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

/**
 * Class WeatherStatusService
 *
 * @package OCA\WeatherStatus\Service
 *
 * @psalm-import-type WeatherStatusForecast from ResponseDefinitions
 * @psalm-import-type WeatherStatusSuccess from ResponseDefinitions
 * @psalm-import-type WeatherStatusLocationWithSuccess from ResponseDefinitions
 * @psalm-import-type WeatherStatusLocationWithMode from ResponseDefinitions
 */
class WeatherStatusService {
	public const MODE_BROWSER_LOCATION = 1;
	public const MODE_MANUAL_LOCATION = 2;

	private IClient $client;
	private ICache $cache;
	private string $version;

	public function __construct(
		private IClientService $clientService,
		private IConfig $config,
		private IL10N $l10n,
		private LoggerInterface $logger,
		private IAccountManager $accountManager,
		private IUserManager $userManager,
		private IAppManager $appManager,
		private ICacheFactory $cacheFactory,
		private ?string $userId
	) {
		$this->version = $appManager->getAppVersion(Application::APP_ID);
		$this->client = $clientService->newClient();
		$this->cache = $cacheFactory->createDistributed('weatherstatus');
	}

	/**
	 * Change the weather status mode. There are currently 2 modes:
	 * - ask the browser
	 * - use the user defined address
	 * @param int $mode New mode
	 * @return WeatherStatusSuccess success state
	 */
	public function setMode(int $mode): array {
		$this->config->setUserValue($this->userId, Application::APP_ID, 'mode', strval($mode));
		return ['success' => true];
	}

	/**
	 * Get favorites list
	 * @return string[]
	 */
	public function getFavorites(): array {
		$favoritesJson = $this->config->getUserValue($this->userId, Application::APP_ID, 'favorites', '');
		return json_decode($favoritesJson, true) ?: [];
	}

	/**
	 * Set favorites list
	 * @param string[] $favorites
	 * @return WeatherStatusSuccess success state
	 */
	public function setFavorites(array $favorites): array {
		$this->config->setUserValue($this->userId, Application::APP_ID, 'favorites', json_encode($favorites));
		return ['success' => true];
	}

	/**
	 * Try to use the address set in user personal settings as weather location
	 *
	 * @return WeatherStatusLocationWithSuccess with success state and address information
	 */
	public function usePersonalAddress(): array {
		$account = $this->accountManager->getAccount($this->userManager->get($this->userId));
		try {
			$address = $account->getProperty('address')->getValue();
		} catch (PropertyDoesNotExistException $e) {
			return ['success' => false];
		}
		if ($address === '') {
			return ['success' => false];
		}
		return $this->setAddress($address);
	}

	/**
	 * Set address and resolve it to get coordinates
	 * or directly set coordinates and get address with reverse geocoding
	 *
	 * @param string|null $address Any approximative or exact address
	 * @param float|null $lat Latitude in decimal degree format
	 * @param float|null $lon Longitude in decimal degree format
	 * @return WeatherStatusLocationWithSuccess with success state and address information
	 */
	public function setLocation(?string $address, ?float $lat, ?float $lon): array {
		if (!is_null($lat) && !is_null($lon)) {
			// store coordinates
			$this->config->setUserValue($this->userId, Application::APP_ID, 'lat', strval($lat));
			$this->config->setUserValue($this->userId, Application::APP_ID, 'lon', strval($lon));
			// resolve and store formatted address
			$address = $this->resolveLocation($lat, $lon);
			$address = $address ? $address : $this->l10n->t('Unknown address');
			$this->config->setUserValue($this->userId, Application::APP_ID, 'address', $address);
			// get and store altitude
			$altitude = $this->getAltitude($lat, $lon);
			$this->config->setUserValue($this->userId, Application::APP_ID, 'altitude', strval($altitude));
			return [
				'address' => $address,
				'success' => true,
			];
		} elseif ($address) {
			return $this->setAddress($address);
		} else {
			return ['success' => false];
		}
	}

	/**
	 * Provide address information from coordinates
	 *
	 * @param float $lat Latitude in decimal degree format
	 * @param float $lon Longitude in decimal degree format
	 */
	private function resolveLocation(float $lat, float $lon): ?string {
		$params = [
			'lat' => number_format($lat, 2),
			'lon' => number_format($lon, 2),
			'addressdetails' => 1,
			'format' => 'json',
		];
		$url = 'https://nominatim.openstreetmap.org/reverse';
		$result = $this->requestJSON($url, $params);
		return $this->formatOsmAddress($result);
	}

	/**
	 * Get altitude from coordinates
	 *
	 * @param float $lat Latitude in decimal degree format
	 * @param float $lon Longitude in decimal degree format
	 * @return float altitude in meter
	 */
	private function getAltitude(float $lat, float $lon): float {
		$params = [
			'locations' => $lat . ',' . $lon,
		];
		$url = 'https://api.opentopodata.org/v1/srtm30m';
		$result = $this->requestJSON($url, $params);
		$altitude = 0;
		if (isset($result['results']) && is_array($result['results']) && count($result['results']) > 0
			&& is_array($result['results'][0]) && isset($result['results'][0]['elevation'])) {
			$altitude = floatval($result['results'][0]['elevation']);
		}
		return $altitude;
	}

	/**
	 * @return string Formatted address from JSON nominatim result
	 */
	private function formatOsmAddress(array $json): ?string {
		if (isset($json['address']) && isset($json['display_name'])) {
			$jsonAddr = $json['address'];
			$cityAddress = '';
			// priority : city, town, village, municipality
			if (isset($jsonAddr['city'])) {
				$cityAddress .= $jsonAddr['city'];
			} elseif (isset($jsonAddr['town'])) {
				$cityAddress .= $jsonAddr['town'];
			} elseif (isset($jsonAddr['village'])) {
				$cityAddress .= $jsonAddr['village'];
			} elseif (isset($jsonAddr['municipality'])) {
				$cityAddress .= $jsonAddr['municipality'];
			} else {
				return $json['display_name'];
			}
			// post code
			if (isset($jsonAddr['postcode'])) {
				$cityAddress .= ', ' . $jsonAddr['postcode'];
			}
			// country
			if (isset($jsonAddr['country'])) {
				$cityAddress .= ', ' . $jsonAddr['country'];
				return $cityAddress;
			} else {
				return $json['display_name'];
			}
		} elseif (isset($json['display_name'])) {
			return $json['display_name'];
		}
		return null;
	}

	/**
	 * Set address and resolve it to get coordinates
	 *
	 * @param string $address Any approximative or exact address
	 * @return WeatherStatusLocationWithSuccess with success state and address information (coordinates and formatted address)
	 */
	public function setAddress(string $address): array {
		$addressInfo = $this->searchForAddress($address);
		if (isset($addressInfo['display_name']) && isset($addressInfo['lat']) && isset($addressInfo['lon'])) {
			$formattedAddress = $this->formatOsmAddress($addressInfo);
			$this->config->setUserValue($this->userId, Application::APP_ID, 'address', $formattedAddress);
			$this->config->setUserValue($this->userId, Application::APP_ID, 'lat', strval($addressInfo['lat']));
			$this->config->setUserValue($this->userId, Application::APP_ID, 'lon', strval($addressInfo['lon']));
			$this->config->setUserValue($this->userId, Application::APP_ID, 'mode', strval(self::MODE_MANUAL_LOCATION));
			// get and store altitude
			$altitude = $this->getAltitude(floatval($addressInfo['lat']), floatval($addressInfo['lon']));
			$this->config->setUserValue($this->userId, Application::APP_ID, 'altitude', strval($altitude));
			return [
				'lat' => $addressInfo['lat'],
				'lon' => $addressInfo['lon'],
				'address' => $formattedAddress,
				'success' => true,
			];
		} else {
			return ['success' => false];
		}
	}

	/**
	 * Ask nominatim information about an unformatted address
	 *
	 * @param string Unformatted address
	 * @return array{display_name?: string, lat?: string, lon?: string, error?: string} Full Nominatim result for the given address
	 */
	private function searchForAddress(string $address): array {
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
		if ($results['error'] !== null) {
			return $results;
		}
		if (count($results) > 0) {
			return $results[0];
		}
		return ['error' => $this->l10n->t('No result.')];
	}

	/**
	 * Get stored user location
	 *
	 * @return WeatherStatusLocationWithMode which contains coordinates, formatted address and current weather status mode
	 */
	public function getLocation(): array {
		$lat = $this->config->getUserValue($this->userId, Application::APP_ID, 'lat', '');
		$lon = $this->config->getUserValue($this->userId, Application::APP_ID, 'lon', '');
		$address = $this->config->getUserValue($this->userId, Application::APP_ID, 'address', '');
		$mode = $this->config->getUserValue($this->userId, Application::APP_ID, 'mode', self::MODE_MANUAL_LOCATION);
		return [
			'lat' => $lat,
			'lon' => $lon,
			'address' => $address,
			'mode' => intval($mode),
		];
	}

	/**
	 * Get forecast for current location
	 *
	 * @return WeatherStatusForecast[]|array{error: string}|WeatherStatusSuccess which contains success state and filtered forecast data
	 */
	public function getForecast(): array {
		$lat = $this->config->getUserValue($this->userId, Application::APP_ID, 'lat', '');
		$lon = $this->config->getUserValue($this->userId, Application::APP_ID, 'lon', '');
		$alt = $this->config->getUserValue($this->userId, Application::APP_ID, 'altitude', '');
		if (!is_numeric($alt)) {
			$alt = 0;
		}
		if (is_numeric($lat) && is_numeric($lon)) {
			return $this->forecastRequest(floatval($lat), floatval($lon), floatval($alt));
		} else {
			return ['success' => false];
		}
	}

	/**
	 * Actually make the request to the forecast service
	 *
	 * @param float $lat Latitude of requested forecast, in decimal degree format
	 * @param float $lon Longitude of requested forecast, in decimal degree format
	 * @param float $altitude Altitude of requested forecast, in meter
	 * @param int $nbValues Number of forecast values (hours)
	 * @return WeatherStatusForecast[]|array{error: string} Filtered forecast data
	 */
	private function forecastRequest(float $lat, float $lon, float $altitude, int $nbValues = 10): array {
		$params = [
			'lat' => number_format($lat, 2),
			'lon' => number_format($lon, 2),
			'altitude' => $altitude,
		];
		$url = 'https://api.met.no/weatherapi/locationforecast/2.0/compact';
		$weather = $this->requestJSON($url, $params);
		if (isset($weather['properties']) && isset($weather['properties']['timeseries']) && is_array($weather['properties']['timeseries'])) {
			return array_slice($weather['properties']['timeseries'], 0, $nbValues);
		}
		return ['error' => $this->l10n->t('Malformed JSON data.')];
	}

	/**
	 * Make a HTTP GET request and parse JSON result.
	 * Request results are cached until the 'Expires' response header says so
	 *
	 * @param string $url Base URL to query
	 * @param array $params GET parameters
	 * @return array which contains the error message or the parsed JSON result
	 */
	private function requestJSON(string $url, array $params = []): array {
		$cacheKey = $url . '|' . implode(',', $params) . '|' . implode(',', array_keys($params));
		$cacheValue = $this->cache->get($cacheKey);
		if ($cacheValue !== null) {
			return $cacheValue;
		}

		try {
			$options = [
				'headers' => [
					'User-Agent' => 'NextcloudWeatherStatus/' . $this->version . ' nextcloud.com'
				],
			];

			$reqUrl = $url;
			if (count($params) > 0) {
				$paramsContent = http_build_query($params);
				$reqUrl = $url . '?' . $paramsContent;
			}

			$response = $this->client->get($reqUrl, $options);
			$body = $response->getBody();
			$headers = $response->getHeaders();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Error')];
			} else {
				$json = json_decode($body, true);

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
		} catch (\Exception $e) {
			$this->logger->warning($url . ' API error : ' . $e->getMessage(), ['exception' => $e]);
			return ['error' => $e->getMessage()];
		}
	}
}
