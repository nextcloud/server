<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Controller;

use OCA\UserStatus\ResponseDefinitions;
use OCA\UserStatus\Service\PredefinedStatusService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

/**
 * @package OCA\UserStatus\Controller
 *
 * @psalm-import-type UserStatusPredefined from ResponseDefinitions
 */
class PredefinedStatusController extends OCSController {

	/** @var PredefinedStatusService */
	private $predefinedStatusService;

	/**
	 * AStatusController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param PredefinedStatusService $predefinedStatusService
	 */
	public function __construct(string $appName,
		IRequest $request,
		PredefinedStatusService $predefinedStatusService) {
		parent::__construct($appName, $request);
		$this->predefinedStatusService = $predefinedStatusService;
	}

	/**
	 * Get all predefined messages
	 *
	 * @NoAdminRequired
	 *
	 * @return DataResponse<Http::STATUS_OK, UserStatusPredefined[], array{}>
	 *
	 * 200: Predefined statuses returned
	 */
	#[ApiRoute(verb: 'GET', url: '/api/v1/predefined_statuses/')]
	public function findAll():DataResponse {
		// Filtering out the invisible one, that should only be set by API
		return new DataResponse(array_filter($this->predefinedStatusService->getDefaultStatuses(), function (array $status) {
			return !array_key_exists('visible', $status) || $status['visible'] === true;
		}));
	}
}
