<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Controller;

use OC\Core\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\Teams\ITeamManager;
use OCP\Teams\Team;

/**
 * @psalm-import-type CoreTeamResource from ResponseDefinitions
 * @psalm-import-type CoreTeam from ResponseDefinitions
 * @property $userId string
 */
class TeamsApiController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private ITeamManager $teamManager,
		private ?string $userId,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get all resources of a team
	 *
	 * @param string $teamId Unique id of the team
	 * @return DataResponse<Http::STATUS_OK, array{resources: list<CoreTeamResource>}, array{}>
	 *
	 * 200: Resources returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/{teamId}/resources', root: '/teams')]
	public function resolveOne(string $teamId): DataResponse {
		/**
		 * @var list<CoreTeamResource> $resolvedResources
		 * @psalm-suppress PossiblyNullArgument The route is limited to logged-in users
		 */
		$resolvedResources = $this->teamManager->getSharedWith($teamId, $this->userId);

		return new DataResponse(['resources' => $resolvedResources]);
	}

	/**
	 * Get all teams of a resource
	 *
	 * @param string $providerId Identifier of the provider (e.g. deck, talk, collectives)
	 * @param string $resourceId Unique id of the resource to list teams for (e.g. deck board id)
	 * @return DataResponse<Http::STATUS_OK, array{teams: list<CoreTeam>}, array{}>
	 *
	 * 200: Teams returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/resources/{providerId}/{resourceId}', root: '/teams')]
	public function listTeams(string $providerId, string $resourceId): DataResponse {
		/** @psalm-suppress PossiblyNullArgument The route is limited to logged-in users */
		$teams = $this->teamManager->getTeamsForResource($providerId, $resourceId, $this->userId);
		/** @var list<CoreTeam> $teams */
		$teams = array_values(array_map(function (Team $team) {
			$response = $team->jsonSerialize();
			/** @psalm-suppress PossiblyNullArgument The route is limited to logged in users */
			$response['resources'] = $this->teamManager->getSharedWith($team->getId(), $this->userId);
			return $response;
		}, $teams));

		return new DataResponse([
			'teams' => $teams,
		]);
	}
}
