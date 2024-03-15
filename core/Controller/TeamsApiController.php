<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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

use OCA\Core\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\Teams\ITeamManager;
use OCP\Teams\Team;

/**
 * @psalm-import-type CoreTeamResource from ResponseDefinitions
 * @psalm-import-type CoreTeam from ResponseDefinitions
 * @property $userId string
 */
class TeamsApiController extends \OCP\AppFramework\OCSController {
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
	 * @return DataResponse<Http::STATUS_OK, array{resources: CoreTeamResource[]}, array{}>
	 *
	 * 200: Resources returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/{teamId}/resources', root: '/teams')]
	public function resolveOne(string $teamId): DataResponse {
		/**
		 * @var CoreTeamResource[] $resolvedResources
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
	 * @return DataResponse<Http::STATUS_OK, array{teams: CoreTeam[]}, array{}>
	 *
	 * 200: Teams returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/resources/{providerId}/{resourceId}', root: '/teams')]
	public function listTeams(string $providerId, string $resourceId): DataResponse {
		/** @psalm-suppress PossiblyNullArgument The route is limited to logged-in users */
		$teams = $this->teamManager->getTeamsForResource($providerId, $resourceId, $this->userId);
		/** @var CoreTeam[] $teams */
		$teams = array_map(function (Team $team) {
			$response = $team->jsonSerialize();
			/** @psalm-suppress PossiblyNullArgument The route is limited to logged in users */
			$response['resources'] = $this->teamManager->getSharedWith($team->getId(), $this->userId);
			return $response;
		}, $teams);

		return new DataResponse([
			'teams' => $teams,
		]);
	}
}
