<?php

declare(strict_types=1);


/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Provisioning_API\Controller;


use OCA\Circles\CirclesManager;
use OCA\Circles\Exceptions\FederatedUserException;
use OCA\Circles\Exceptions\FederatedUserNotFoundException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\InvalidIdException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\SingleCircleNotFoundException;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\CircleProbe;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\Circles\ICirclesManager;
use OCP\IRequest;
use Psr\Log\LoggerInterface;


/**
 * Class CirclesController
 *
 * @package OCA\Provisioning_API\Controller
 */
class CirclesController extends OcsController {


	const DETAILS_NONE = 0;
	const DETAILS_MIN = 1;
	const DETAILS_MED = 2;
	const DETAILS_HIGH = 3;
	const DETAILS_FULL = 9;

	/** @var LoggerInterface */
	private $logger;

	/** @var CirclesManager */
	private $circlesManager;


	/**
	 * CirclesController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param LoggerInterface $logger
	 * @param ICirclesManager $circlesManager
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		LoggerInterface $logger,
		ICirclesManager $circlesManager
	) {
		parent::__construct($appName, $request);

		$this->logger = $logger;
		$this->circlesManager = $circlesManager;
	}


	/**
	 * returns a list of circles
	 *
	 * @NoAdminRequired
	 *
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return DataResponse
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 * @throws FederatedUserException
	 * @throws FederatedUserNotFoundException
	 * @throws InvalidIdException
	 * @throws SingleCircleNotFoundException
	 */
	public function getCircles(string $search = '', int $limit = 0, int $offset = 0): DataResponse {
		$this->circlesManager->startSession();

		$probe = new CircleProbe();
		$probe->filterHiddenCircles()
			  ->filterBackendCircles();

		$circles = array_map(
			function (Circle $circle) {
				return [
					'singleId' => $circle->getSingleId(),
					'displayName' => $circle->getDisplayName()
				];
			}, $this->circlesManager->getCircles($probe)
		);

		return new DataResponse(['circles' => $circles]);
	}


	/**
	 * returns a list of entities as Admin
	 *
	 * @param string $search
	 * @param string $filter
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return DataResponse
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 */
	public function entitiesAsAdmin(
		string $search = '',
		string $filter = '',
		int $limit = 0,
		int $offset = 0
	): DataResponse {
		return $this->getEntitiesAsAdmin(
			$search,
			$filter,
			$limit,
			$offset,
			self::DETAILS_NONE,
			true
		);
	}


	/**
	 * returns a list of entities as Admin
	 *
	 * @param string $search
	 * @param string $filter
	 * @param int $level
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return DataResponse
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 */
	public function detailedEntitiesAsAdmin(
		string $search = '',
		string $filter = '',
		int $level = self::DETAILS_MIN,
		int $limit = 0,
		int $offset = 0
	): DataResponse {
		return $this->getEntitiesAsAdmin(
			$search,
			$filter,
			$limit,
			$offset,
			$level,
			true
		);
	}


	/**
	 * @param string $search
	 * @param string $filter
	 * @param int $limit
	 * @param int $offset
	 * @param bool $details
	 * @param bool $asAdmin
	 *
	 * @return DataResponse
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 */
	private function getEntitiesAsAdmin(
		string $search = '',
		string $filter = '',
		int $limit = 0,
		int $offset = 0,
		int $details = self::DETAILS_NONE,
		bool $asAdmin = false
	): DataResponse {
		$this->circlesManager->startSuperSession();

		$probe = new CircleProbe();
		$probe->setItemsOffset($offset)
			  ->setItemsLimit($limit);

		$probe->includeSingleCircles();
		$probe->visitSingleCircles();

		$this->manageSearchString($probe, $search);
		$this->manageFilterString($probe, $filter, $asAdmin);

		return new DataResponse(
			[
				'entities' => $this->formatCircles($this->circlesManager->getCircles($probe), $details)
			]
		);
	}


	/**
	 * @param CircleProbe $probe
	 * @param string $search
	 */
	private function manageSearchString(CircleProbe $probe, string $search): void {
		if ($search === '') {
			return;
		}

		$circle = new Circle();
		$circle->setDisplayName($search);

		$probe->setFilterCircle($circle);
	}


	/**
	 * @param CircleProbe $probe
	 * @param string $filter
	 * @param bool $asAdmin
	 */
	private function manageFilterString(CircleProbe $probe, string $filter, bool $asAdmin = false): void {
		$this->manageFilters(
			$probe,
			explode(
				',',
				strtolower(
					str_replace(' ', '', $filter)
				)
			),
			$asAdmin
		);
	}


	/**
	 * @param CircleProbe $probe
	 * @param array $filters
	 * @param bool $asAdmin
	 */
	private function manageFilters(CircleProbe $probe, array $filters, bool $asAdmin = false): void {
		foreach ($filters as $filter) {
			$status = true;
			if (substr($filter, 0, 1) === '-') {
				$status = false;
				$filter = substr($filter, 1);
			}

			switch ($filter) {
				case 'single':
					$probe->includeSingleCircles($status);
					break;

				case 'system':
					$probe->includeSystemCircles($status);
					break;
			}
		}
	}


	/**
	 * @param array $circles
	 * @param int $details
	 *
	 * @return array
	 */
	private function formatCircles(array $circles, int $details = self::DETAILS_NONE): array {
		if ($details === self::DETAILS_NONE) {
			return array_map(
				function (Circle $circle): string {
					return $circle->getSingleId();
				},
				$circles
			);
		}

		return array_map(
			function (Circle $circle) use ($details): array {
				return $this->formatCircle($circle, $details);
			},
			$circles
		);
	}


	/**
	 * @param Circle $circle
	 * @param int $details
	 *
	 * @return array
	 */
	private function formatCircle(Circle $circle, int $details = self::DETAILS_MIN): array {
		if ($details === self::DETAILS_FULL) {
			return json_decode(json_encode($circle), true);
		}

		$result = [
			'singleId' => $circle->getSingleId(),
			'displayName' => $circle->getDisplayName(),
			'definition' => $this->circlesManager->getDefinition($circle),
			'population' => $circle->getPopulation(),
			'config' => $circle->getConfig(),
			'source' => $circle->getSource()
		];

		if ($details >= self::DETAILS_MED) {
			$result = array_merge(
				$result,
				[
					'name' => $circle->getName(),
					'sanitizedName' => $circle->getSanitizedName(),
					'owner' => $this->formatMember($circle->getOwner(), $details)
				]
			);
		}

		if ($details >= self::DETAILS_HIGH) {

		}

		return $result;
	}


	/**
	 * @param array $members
	 * @param int $details
	 *
	 * @return array
	 */
	private function formatMembers(array $members, int $details = self::DETAILS_NONE): array {
		if ($details === self::DETAILS_NONE) {
			return array_map(
				function (Member $member): string {
					return $member->getSingleId();
				},
				$members
			);
		}

		return array_map(
			function (Member $member) use ($details): array {
				return $this->formatMember($member, $details);
			},
			$members
		);
	}


	/**
	 * @param Member $member
	 * @param int $details
	 *
	 * @return array
	 */
	private function formatMember(Member $member, int $details = self::DETAILS_NONE): array {
		if ($details === self::DETAILS_FULL) {
			return json_decode(json_encode($member), true);
		}

		$result = [
			'singleId' => $member->getSingleId(),
			'displayName' => $member->getDisplayName(),
			'userType' => $member->getUserType(),
			'instance' => $member->getInstance(),
			'local' => $member->isLocal()
		];

		if ($details >= self::DETAILS_MED) {
			$result = array_merge(
				$result,
				[
					'id' => $member->getId(),
					'circleId' => $member->getCircleId(),
					'level ' => $member->getLevel()
				]
			);
		}

		if ($details >= self::DETAILS_HIGH) {

		}

		return $result;
	}

}
