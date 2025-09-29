<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Controller;

use Generator;
use OC\Collaboration\Collaborators\SearchResult;
use OC\Share\Share;
use OCA\Files_Sharing\ResponseDefinitions;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCSController;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\Constants;
use OCP\GlobalScale\IConfig as GlobalScaleIConfig;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Share\IShare;
use function array_slice;
use function array_values;
use function usort;

/**
 * @psalm-import-type Files_SharingShareesSearchResult from ResponseDefinitions
 * @psalm-import-type Files_SharingShareesRecommendedResult from ResponseDefinitions
 */
class ShareesAPIController extends OCSController {

	/** @var int */
	protected $offset = 0;

	/** @var int */
	protected $limit = 10;

	/** @var Files_SharingShareesSearchResult */
	protected $result = [
		'exact' => [
			'users' => [],
			'groups' => [],
			'remotes' => [],
			'remote_groups' => [],
			'emails' => [],
			'circles' => [],
			'rooms' => [],
		],
		'users' => [],
		'groups' => [],
		'remotes' => [],
		'remote_groups' => [],
		'emails' => [],
		'lookup' => [],
		'circles' => [],
		'rooms' => [],
		'lookupEnabled' => false,
	];

	protected $reachedEndFor = [];

	public function __construct(
		string $appName,
		IRequest $request,
		protected ?string $userId,
		protected IConfig $config,
		protected IURLGenerator $urlGenerator,
		protected IManager $shareManager,
		protected ISearch $collaboratorSearch,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Search for sharees
	 *
	 * @param string $search Text to search for
	 * @param string|null $itemType Limit to specific item types
	 * @param int $page Page offset for searching
	 * @param int $perPage Limit amount of search results per page
	 * @param int|list<int>|null $shareType Limit to specific share types
	 * @param bool $lookup If a global lookup should be performed too
	 * @return DataResponse<Http::STATUS_OK, Files_SharingShareesSearchResult, array{Link?: string}>
	 * @throws OCSBadRequestException Invalid search parameters
	 *
	 * 200: Sharees search result returned
	 */
	#[NoAdminRequired]
	public function search(string $search = '', ?string $itemType = null, int $page = 1, int $perPage = 200, $shareType = null, bool $lookup = false): DataResponse {

		// only search for string larger than a given threshold
		$threshold = $this->config->getSystemValueInt('sharing.minSearchStringLength', 0);
		if (strlen($search) < $threshold) {
			return new DataResponse($this->result);
		}

		if ($this->shareManager->sharingDisabledForUser($this->userId)) {
			return new DataResponse($this->result);
		}

		// never return more than the max. number of results configured in the config.php
		$maxResults = $this->config->getSystemValueInt('sharing.maxAutocompleteResults', Constants::SHARING_MAX_AUTOCOMPLETE_RESULTS_DEFAULT);
		if ($maxResults > 0) {
			$perPage = min($perPage, $maxResults);
		}
		if ($perPage <= 0) {
			throw new OCSBadRequestException('Invalid perPage argument');
		}
		if ($page <= 0) {
			throw new OCSBadRequestException('Invalid page');
		}

		$shareTypes = [
			IShare::TYPE_USER,
		];

		if ($itemType === null) {
			throw new OCSBadRequestException('Missing itemType');
		} elseif ($itemType === 'file' || $itemType === 'folder') {
			if ($this->shareManager->allowGroupSharing()) {
				$shareTypes[] = IShare::TYPE_GROUP;
			}

			if ($this->isRemoteSharingAllowed($itemType)) {
				$shareTypes[] = IShare::TYPE_REMOTE;
			}

			if ($this->isRemoteGroupSharingAllowed($itemType)) {
				$shareTypes[] = IShare::TYPE_REMOTE_GROUP;
			}

			if ($this->shareManager->shareProviderExists(IShare::TYPE_EMAIL)) {
				$shareTypes[] = IShare::TYPE_EMAIL;
			}

			if ($this->shareManager->shareProviderExists(IShare::TYPE_ROOM)) {
				$shareTypes[] = IShare::TYPE_ROOM;
			}

			if ($this->shareManager->shareProviderExists(IShare::TYPE_SCIENCEMESH)) {
				$shareTypes[] = IShare::TYPE_SCIENCEMESH;
			}
		} else {
			if ($this->shareManager->allowGroupSharing()) {
				$shareTypes[] = IShare::TYPE_GROUP;
			}
			$shareTypes[] = IShare::TYPE_EMAIL;
		}

		// FIXME: DI
		if (Server::get(IAppManager::class)->isEnabledForUser('circles') && class_exists('\OCA\Circles\ShareByCircleProvider')) {
			$shareTypes[] = IShare::TYPE_CIRCLE;
		}

		if ($this->shareManager->shareProviderExists(IShare::TYPE_SCIENCEMESH)) {
			$shareTypes[] = IShare::TYPE_SCIENCEMESH;
		}

		if ($itemType === 'calendar') {
			$shareTypes[] = IShare::TYPE_REMOTE;
		}

		if ($shareType !== null && is_array($shareType)) {
			$shareTypes = array_intersect($shareTypes, $shareType);
		} elseif (is_numeric($shareType)) {
			$shareTypes = array_intersect($shareTypes, [(int)$shareType]);
		}
		sort($shareTypes);

		$this->limit = $perPage;
		$this->offset = $perPage * ($page - 1);

		// In global scale mode we always search the lookup server
		$this->result['lookupEnabled'] = Server::get(GlobalScaleIConfig::class)->isGlobalScaleEnabled();
		// TODO: Reconsider using lookup server for non-global-scale federation

		[$result, $hasMoreResults] = $this->collaboratorSearch->search($search, $shareTypes, $this->result['lookupEnabled'], $this->limit, $this->offset);

		// extra treatment for 'exact' subarray, with a single merge expected keys might be lost
		if (isset($result['exact'])) {
			$result['exact'] = array_merge($this->result['exact'], $result['exact']);
		}
		$this->result = array_merge($this->result, $result);
		$response = new DataResponse($this->result);

		if ($hasMoreResults) {
			$response->setHeaders(['Link' => $this->getPaginationLink($page, [
				'search' => $search,
				'itemType' => $itemType,
				'shareType' => $shareTypes,
				'perPage' => $perPage,
			])]);
		}

		return $response;
	}

	/**
	 * @param string $user
	 * @param int $shareType
	 *
	 * @return Generator<array<string>>
	 */
	private function getAllShareesByType(string $user, int $shareType): Generator {
		$offset = 0;
		$pageSize = 50;

		while (count($page = $this->shareManager->getSharesBy(
			$user,
			$shareType,
			null,
			false,
			$pageSize,
			$offset
		))) {
			foreach ($page as $share) {
				yield [$share->getSharedWith(), $share->getSharedWithDisplayName() ?? $share->getSharedWith()];
			}

			$offset += $pageSize;
		}
	}

	private function sortShareesByFrequency(array $sharees): array {
		usort($sharees, function (array $s1, array $s2): int {
			return $s2['count'] - $s1['count'];
		});
		return $sharees;
	}

	private $searchResultTypeMap = [
		IShare::TYPE_USER => 'users',
		IShare::TYPE_GROUP => 'groups',
		IShare::TYPE_REMOTE => 'remotes',
		IShare::TYPE_REMOTE_GROUP => 'remote_groups',
		IShare::TYPE_EMAIL => 'emails',
	];

	private function getAllSharees(string $user, array $shareTypes): ISearchResult {
		$result = [];
		foreach ($shareTypes as $shareType) {
			$sharees = $this->getAllShareesByType($user, $shareType);
			$shareTypeResults = [];
			foreach ($sharees as [$sharee, $displayname]) {
				if (!isset($this->searchResultTypeMap[$shareType]) || trim($sharee) === '') {
					continue;
				}

				if (!isset($shareTypeResults[$sharee])) {
					$shareTypeResults[$sharee] = [
						'count' => 1,
						'label' => $displayname,
						'value' => [
							'shareType' => $shareType,
							'shareWith' => $sharee,
						],
					];
				} else {
					$shareTypeResults[$sharee]['count']++;
				}
			}
			$result = array_merge($result, array_values($shareTypeResults));
		}

		$top5 = array_slice(
			$this->sortShareesByFrequency($result),
			0,
			5
		);

		$searchResult = new SearchResult();
		foreach ($this->searchResultTypeMap as $int => $str) {
			$searchResult->addResultSet(new SearchResultType($str), [], []);
			foreach ($top5 as $x) {
				if ($x['value']['shareType'] === $int) {
					$searchResult->addResultSet(new SearchResultType($str), [], [$x]);
				}
			}
		}
		return $searchResult;
	}

	/**
	 * Find recommended sharees
	 *
	 * @param string $itemType Limit to specific item types
	 * @param int|list<int>|null $shareType Limit to specific share types
	 * @return DataResponse<Http::STATUS_OK, Files_SharingShareesRecommendedResult, array{}>
	 *
	 * 200: Recommended sharees returned
	 */
	#[NoAdminRequired]
	public function findRecommended(string $itemType, $shareType = null): DataResponse {
		$shareTypes = [
			IShare::TYPE_USER,
		];

		if ($itemType === 'file' || $itemType === 'folder') {
			if ($this->shareManager->allowGroupSharing()) {
				$shareTypes[] = IShare::TYPE_GROUP;
			}

			if ($this->isRemoteSharingAllowed($itemType)) {
				$shareTypes[] = IShare::TYPE_REMOTE;
			}

			if ($this->isRemoteGroupSharingAllowed($itemType)) {
				$shareTypes[] = IShare::TYPE_REMOTE_GROUP;
			}

			if ($this->shareManager->shareProviderExists(IShare::TYPE_EMAIL)) {
				$shareTypes[] = IShare::TYPE_EMAIL;
			}

			if ($this->shareManager->shareProviderExists(IShare::TYPE_ROOM)) {
				$shareTypes[] = IShare::TYPE_ROOM;
			}
		} else {
			$shareTypes[] = IShare::TYPE_GROUP;
			$shareTypes[] = IShare::TYPE_EMAIL;
		}

		// FIXME: DI
		if (Server::get(IAppManager::class)->isEnabledForUser('circles') && class_exists('\OCA\Circles\ShareByCircleProvider')) {
			$shareTypes[] = IShare::TYPE_CIRCLE;
		}

		if (isset($_GET['shareType']) && is_array($_GET['shareType'])) {
			$shareTypes = array_intersect($shareTypes, $_GET['shareType']);
			sort($shareTypes);
		} elseif (is_numeric($shareType)) {
			$shareTypes = array_intersect($shareTypes, [(int)$shareType]);
			sort($shareTypes);
		}

		return new DataResponse(
			$this->getAllSharees($this->userId, $shareTypes)->asArray()
		);
	}

	/**
	 * Method to get out the static call for better testing
	 *
	 * @param string $itemType
	 * @return bool
	 */
	protected function isRemoteSharingAllowed(string $itemType): bool {
		try {
			// FIXME: static foo makes unit testing unnecessarily difficult
			$backend = Share::getBackend($itemType);
			return $backend->isShareTypeAllowed(IShare::TYPE_REMOTE);
		} catch (\Exception $e) {
			return false;
		}
	}

	protected function isRemoteGroupSharingAllowed(string $itemType): bool {
		try {
			// FIXME: static foo makes unit testing unnecessarily difficult
			$backend = Share::getBackend($itemType);
			return $backend->isShareTypeAllowed(IShare::TYPE_REMOTE_GROUP);
		} catch (\Exception $e) {
			return false;
		}
	}


	/**
	 * Generates a bunch of pagination links for the current page
	 *
	 * @param int $page Current page
	 * @param array $params Parameters for the URL
	 * @return string
	 */
	protected function getPaginationLink(int $page, array $params): string {
		if ($this->isV2()) {
			$url = $this->urlGenerator->getAbsoluteURL('/ocs/v2.php/apps/files_sharing/api/v1/sharees') . '?';
		} else {
			$url = $this->urlGenerator->getAbsoluteURL('/ocs/v1.php/apps/files_sharing/api/v1/sharees') . '?';
		}
		$params['page'] = $page + 1;
		return '<' . $url . http_build_query($params) . '>; rel="next"';
	}

	/**
	 * @return bool
	 */
	protected function isV2(): bool {
		return $this->request->getScriptName() === '/ocs/v2.php';
	}
}
