<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Dashboard\Controller;

use OCA\Dashboard\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IManager;
use OCP\Dashboard\IOptionWidget;
use OCP\IRequest;
use OCP\Teams\ITeamManager;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @psalm-import-type DashboardTeamWidget from ResponseDefinitions
 * @psalm-import-type DashboardTeamWidgetResult from ResponseDefinitions
 */
class TeamDashboardApiController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private IManager $dashboardManager,
		private ITeamManager $teamManager,
		private ?string $userId,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/** @psalm-assert !null $this->userId */
	private function authorize(string $teamId): void {
		if ($this->userId === null || !$this->teamManager->isMemberOfTeam($teamId, $this->userId)) {
			throw new OCSNotFoundException('Team not found');
		}
	}

	/**
	 * List the team-aware widgets available to the current user
	 *
	 * @param string $teamId Team identifier
	 * @return DataResponse<Http::STATUS_OK, array<string, DashboardTeamWidget>, array{}>
	 * @throws OCSNotFoundException Team not found or inaccessible
	 *
	 * 200: Team widgets returned
	 * 404: Team not found or inaccessible
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/teams/{teamId}/widgets')]
	public function getWidgets(string $teamId): DataResponse {
		$this->authorize($teamId);
		$widgets = [];
		foreach ($this->dashboardManager->getTeamWidgets() as $id => $widget) {
			try {
				$widgets[$id] = [
					'id' => $widget->getId(),
					'title' => $widget->getTitle(),
					'order' => $widget->getOrder(),
					'icon_url' => $widget instanceof IIconWidget ? $widget->getIconUrl() : '',
					'icon_class' => $widget->getIconClass(),
					'item_icons_round' => $widget instanceof IOptionWidget && $widget->getWidgetOptions()->withRoundItemIcons(),
					'widget_url' => $widget->getUrl(),
				];
			} catch (Throwable $exception) {
				$this->logger->error('Failed to load team widget metadata', ['widget' => $id, 'exception' => $exception]);
			}
		}
		return new DataResponse($widgets);
	}

	/**
	 * Get dashboard items scoped to the team and authenticated user
	 *
	 * @param string $teamId Team identifier
	 * @param list<string> $widgets Widget identifiers; empty means all team widgets
	 * @param int $limit Maximum items per widget
	 * @psalm-param int<1, 30> $limit
	 * @param array<string, string> $sinceIds Optional per-widget cursors
	 * @return DataResponse<Http::STATUS_OK, array<string, DashboardTeamWidgetResult>, array{}>
	 * @throws OCSNotFoundException Team not found or inaccessible
	 * @throws OCSBadRequestException Invalid limit, widget identifier or cursor
	 *
	 * 200: Team widget results returned
	 * 400: Invalid limit, widget identifier or cursor
	 * 404: Team not found or inaccessible
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/teams/{teamId}/widget-items')]
	public function getWidgetItems(string $teamId, array $widgets = [], int $limit = 7, array $sinceIds = []): DataResponse {
		$this->authorize($teamId);
		$providers = $this->dashboardManager->getTeamWidgets();
		if ($limit < 1 || $limit > 30) {
			throw new OCSBadRequestException('Limit must be between 1 and 30');
		}
		foreach ($widgets as $id) {
			if (!is_string($id) || !isset($providers[$id])) {
				throw new OCSBadRequestException('Unknown team widget');
			}
		}
		foreach ($sinceIds as $id => $since) {
			if (!isset($providers[$id]) || !is_string($since) || strlen($since) > 1024) {
				throw new OCSBadRequestException('Invalid widget cursor');
			}
		}
		$results = [];
		foreach ($providers as $id => $widget) {
			if ($widgets !== [] && !in_array($id, $widgets, true)) {
				continue;
			}
			try {
				$items = $widget->getTeamItems($teamId, $this->userId, $sinceIds[$id] ?? null, $limit);
				$results[$id] = $items === null
					? ['status' => 'unavailable']
					: ['status' => 'ok', 'data' => $items->jsonSerialize()];
			} catch (Throwable $exception) {
				$this->logger->error('Failed to load team widget items', ['widget' => $id, 'team' => $teamId, 'exception' => $exception]);
				$results[$id] = ['status' => 'error'];
			}
		}
		return new DataResponse($results);
	}
}
