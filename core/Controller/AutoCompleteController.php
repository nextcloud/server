<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Controller;

use OC\AppFramework\Http\PaginationTrait;
use OC\Core\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\Collaboration\AutoComplete\AutoCompleteFilterEvent;
use OCP\Collaboration\AutoComplete\IManager;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Share\IShare;

/**
 * @psalm-import-type CoreAutocompleteResult from ResponseDefinitions
 */
class AutoCompleteController extends OCSController {
	use PaginationTrait;

	public function __construct(
		string $appName,
		IRequest $request,
		private ISearch $collaboratorSearch,
		private IManager $autoCompleteManager,
		private IEventDispatcher $dispatcher,
		private IURLGenerator $urlGenerator,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Autocomplete a query
	 *
	 * @param string $search Text to search for
	 * @param string|null $itemType Type of the items to search for
	 * @param string|null $itemId ID of the items to search for
	 * @param string|null $sorter can be piped, top priority first, e.g.: "commenters|share-recipients"
	 * @param list<non-negative-int> $shareTypes Types of shares to search for
	 * @param positive-int $limit Maximum number of results to return
	 * @param non-negative-int $offset Offset for searching
	 *
	 * @return DataResponse<Http::STATUS_OK, list<CoreAutocompleteResult>, array{Link?: string}>
	 *
	 * 200: Autocomplete results returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/autocomplete/get', root: '/core')]
	public function get(string $search, ?string $itemType, ?string $itemId, ?string $sorter = null, array $shareTypes = [IShare::TYPE_USER], int $limit = 10, int $offset = 0): DataResponse {
		// if enumeration/user listings are disabled, we'll receive an empty
		// result from search() – thus nothing else to do here.
		[$results, $hasMoreResults] = $this->collaboratorSearch->search($search, $shareTypes, false, $limit, $offset);

		$event = new AutoCompleteFilterEvent(
			$results,
			$search,
			$itemType,
			$itemId,
			$sorter,
			$shareTypes,
			$limit,
		);
		$this->dispatcher->dispatchTyped($event);
		$results = $event->getResults();

		$exactMatches = $results['exact'];
		unset($results['exact']);
		$results = array_merge_recursive($exactMatches, $results);

		if ($sorter !== null) {
			$sorters = array_reverse(explode('|', $sorter));
			$this->autoCompleteManager->runSorters($sorters, $results, [
				'itemType' => $itemType,
				'itemId' => $itemId,
			]);
		}

		// transform to expected format
		$results = $this->prepareResultArray($results);

		// $hasMoreResults comes from the search backend, not from the (differently shaped,
		// possibly filtered) $results, so it is passed through as-is rather than re-derived.
		$headers = $this->buildOffsetNextPageLinkHeader($hasMoreResults, [
			'search' => $search,
			'itemType' => $itemType,
			'itemId' => $itemId,
			'sorter' => $sorter,
			'shareTypes' => $shareTypes,
		], $limit, $offset);

		return new DataResponse($results, headers: $headers);
	}

	/**
	 * @return list<CoreAutocompleteResult>
	 */
	protected function prepareResultArray(array $results): array {
		$output = [];
		/** @var string $type */
		foreach ($results as $type => $subResult) {
			foreach ($subResult as $result) {
				/** @var ?string $icon */
				$icon = array_key_exists('icon', $result) ? $result['icon'] : null;

				/** @var string $label */
				$label = $result['label'];

				/** @var ?string $subline */
				$subline = array_key_exists('subline', $result) ? $result['subline'] : null;

				/** @var ?array{status: string, message: ?string, icon: ?string, clearAt: ?int} $status */
				$status = array_key_exists('status', $result) && is_array($result['status']) && !empty($result['status']) ? $result['status'] : null;

				/** @var ?string $shareWithDisplayNameUnique */
				$shareWithDisplayNameUnique = array_key_exists('shareWithDisplayNameUnique', $result) ? $result['shareWithDisplayNameUnique'] : null;

				$output[] = [
					'id' => (string)$result['value']['shareWith'],
					'label' => $label,
					'icon' => $icon ?? '',
					'source' => $type,
					'status' => $status ?? '',
					'subline' => $subline ?? '',
					'shareWithDisplayNameUnique' => $shareWithDisplayNameUnique ?? '',
				];
			}
		}
		return $output;
	}
}
