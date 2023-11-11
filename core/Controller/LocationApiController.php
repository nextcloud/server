<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
 */


namespace OC\Core\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Location\CouldNotSearchLocationException;
use OCP\Location\ILocationAddress;
use OCP\Location\ILocationManager;
use OCP\PreConditionNotMetException;

class LocationApiController extends \OCP\AppFramework\OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private ILocationManager $locationManager,
		private IL10N $l10n,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @PublicPage
	 *
	 * Get the location providers configuration
	 *
	 * @return DataResponse<Http::STATUS_OK, array{autocomplete: bool}, array{}>
	 *
	 * 200: Supported languages returned
	 */
	public function config(): DataResponse {
		return new DataResponse([
			'autocomplete' => $this->locationManager->canAutocomplete(),
		]);
	}

	/**
	 * @PublicPage
	 * @UserRateThrottle(limit=25, period=120)
	 * @AnonRateThrottle(limit=10, period=120)
	 *
	 * Search for an address
	 *
	 * @param string $address Address to be searched for
	 * @param array $options Options for searching
	 * @return DataResponse<Http::STATUS_OK, ILocationAddress[], array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_PRECONDITION_FAILED|Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string, from?: ?string}, array{}>
	 *
	 * 200: Address found
	 * 400: Failed to search for location
	 * 412: No location provider available
	 */
	public function search(string $address, array $options = []): DataResponse {
		try {
			$addresses = $this->locationManager->search($address, $options);
			return new DataResponse($addresses);
		} catch (PreConditionNotMetException) {
			return new DataResponse(['message' => $this->l10n->t('No location provider available')], Http::STATUS_PRECONDITION_FAILED);
		} catch (CouldNotSearchLocationException $e) {
			return new DataResponse(['message' => $this->l10n->t('Unable to search for location')], Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * @PublicPage
	 * @UserRateThrottle(limit=25, period=120)
	 * @AnonRateThrottle(limit=10, period=120)
	 *
	 * Geocode an address
	 *
	 * @param float $longitude
	 * @param float $latitude
	 * @param array $options Options for searching
	 * @return DataResponse<Http::STATUS_OK, ILocationAddress[], array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_PRECONDITION_FAILED|Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string, from?: ?string}, array{}>
	 *
	 * 200: Address found
	 * 400: Failed to geocode location
	 * 412: No location provider available
	 */
	public function geocode(float $longitude, float $latitude, array $options = []): DataResponse {
		try {
			$addresses = $this->locationManager->geocode($longitude, $latitude, $options);

			return new DataResponse($addresses);
		} catch (PreConditionNotMetException) {
			return new DataResponse(['message' => $this->l10n->t('No location provider available')], Http::STATUS_PRECONDITION_FAILED);
		} catch (CouldNotSearchLocationException $e) {
			return new DataResponse(['message' => $this->l10n->t('Unable to geocode location')], Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * @PublicPage
	 * @UserRateThrottle(limit=25, period=120)
	 * @AnonRateThrottle(limit=10, period=120)
	 *
	 * Autocomplete an address
	 *
	 * @param string $address Address to be autocompleted
	 * @param array $options Options for searching
	 * @return DataResponse<Http::STATUS_OK, ILocationAddress[], array{}>|DataResponse<Http::STATUS_BAD_REQUEST|Http::STATUS_PRECONDITION_FAILED|Http::STATUS_INTERNAL_SERVER_ERROR, array{message: string, from?: ?string}, array{}>
	 *
	 * 200: Address found
	 * 400: Failed to autocomplete location
	 * 412: No location provider available
	 */
	public function autocomplete(string $address, array $options = []): DataResponse {
		try {
			$addresses = $this->locationManager->autocomplete($address, $options);
			return new DataResponse($addresses);
		} catch (PreConditionNotMetException) {
			return new DataResponse(['message' => $this->l10n->t('No location autocomplete provider available')], Http::STATUS_PRECONDITION_FAILED);
		} catch (CouldNotSearchLocationException $e) {
			return new DataResponse(['message' => $this->l10n->t('Unable to autocomplete location')], Http::STATUS_BAD_REQUEST);
		}
	}
}
