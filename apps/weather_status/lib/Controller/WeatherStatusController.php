<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Julien Veyssier
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
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
namespace OCA\WeatherStatus\Controller;

use OCA\WeatherStatus\ResponseDefinitions;
use OCA\WeatherStatus\Service\WeatherStatusService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

/**
 * @psalm-import-type WeatherStatusForecast from ResponseDefinitions
 * @psalm-import-type WeatherStatusSuccess from ResponseDefinitions
 * @psalm-import-type WeatherStatusLocation from ResponseDefinitions
 * @psalm-import-type WeatherStatusLocationWithSuccess from ResponseDefinitions
 * @psalm-import-type WeatherStatusLocationWithMode from ResponseDefinitions
 */
class WeatherStatusController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private WeatherStatusService $service,
		private ?string $userId,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 *
	 * Try to use the address set in user personal settings as weather location
	 *
	 * @return DataResponse<Http::STATUS_OK, WeatherStatusLocationWithSuccess, array{}>
	 *
	 * 200: Address updated
	 */
	public function usePersonalAddress(): DataResponse {
		return new DataResponse($this->service->usePersonalAddress());
	}

	/**
	 * @NoAdminRequired
	 *
	 * Change the weather status mode. There are currently 2 modes:
	 * - ask the browser
	 * - use the user defined address
	 *
	 * @param int $mode New mode
	 * @return DataResponse<Http::STATUS_OK, WeatherStatusSuccess, array{}>
	 *
	 * 200: Weather status mode updated
	 */
	public function setMode(int $mode): DataResponse {
		return new DataResponse($this->service->setMode($mode));
	}

	/**
	 * @NoAdminRequired
	 *
	 * Set address and resolve it to get coordinates
	 * or directly set coordinates and get address with reverse geocoding
	 *
	 * @param string|null $address Any approximative or exact address
	 * @param float|null $lat Latitude in decimal degree format
	 * @param float|null $lon Longitude in decimal degree format
	 * @return DataResponse<Http::STATUS_OK, WeatherStatusLocationWithSuccess, array{}>
	 *
	 * 200: Location updated
	 */
	public function setLocation(?string $address, ?float $lat, ?float $lon): DataResponse {
		$currentWeather = $this->service->setLocation($address, $lat, $lon);
		return new DataResponse($currentWeather);
	}

	/**
	 * @NoAdminRequired
	 *
	 * Get stored user location
	 *
	 * @return DataResponse<Http::STATUS_OK, WeatherStatusLocationWithMode, array{}>
	 *
	 * 200: Location returned
	 */
	public function getLocation(): DataResponse {
		$location = $this->service->getLocation();
		return new DataResponse($location);
	}

	/**
	 * @NoAdminRequired
	 *
	 * Get forecast for current location
	 *
	 * @return DataResponse<Http::STATUS_OK, WeatherStatusForecast[]|array{error: string}, array{}>|DataResponse<Http::STATUS_NOT_FOUND, WeatherStatusSuccess, array{}>
	 *
	 * 200: Forecast returned
	 * 404: Forecast not found
	 */
	public function getForecast(): DataResponse {
		$forecast = $this->service->getForecast();
		if (isset($forecast['success']) && $forecast['success'] === false) {
			return new DataResponse($forecast, Http::STATUS_NOT_FOUND);
		} else {
			return new DataResponse($forecast);
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * Get favorites list
	 *
	 * @return DataResponse<Http::STATUS_OK, string[], array{}>
	 *
	 * 200: Favorites returned
	 */
	public function getFavorites(): DataResponse {
		return new DataResponse($this->service->getFavorites());
	}

	/**
	 * @NoAdminRequired
	 *
	 * Set favorites list
	 *
	 * @param string[] $favorites Favorite addresses
	 * @return DataResponse<Http::STATUS_OK, WeatherStatusSuccess, array{}>
	 *
	 * 200: Favorites updated
	 */
	public function setFavorites(array $favorites): DataResponse {
		return new DataResponse($this->service->setFavorites($favorites));
	}
}
