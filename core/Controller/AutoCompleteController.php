<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OC\Core\ResponseDefinitions;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\Collaboration\AutoComplete\AutoCompleteEvent;
use OCP\Collaboration\AutoComplete\AutoCompleteFilterEvent;
use OCP\Collaboration\AutoComplete\IManager;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\Share\IShare;

/**
 * @psalm-import-type CoreAutocompleteResult from ResponseDefinitions
 */
class AutoCompleteController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private ISearch $collaboratorSearch,
		private IManager $autoCompleteManager,
		private IEventDispatcher $dispatcher,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Autocomplete a query
	 *
	 * @param string $search Text to search for
	 * @param string|null $itemType Type of the items to search for
	 * @param string|null $itemId ID of the items to search for
	 * @param string|null $sorter can be piped, top prio first, e.g.: "commenters|share-recipients"
	 * @param list<int> $shareTypes Types of shares to search for
	 * @param int $limit Maximum number of results to return
	 *
	 * @return DataResponse<Http::STATUS_OK, list<CoreAutocompleteResult>, array{}>
	 *
	 * 200: Autocomplete results returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/autocomplete/get', root: '/core')]
	public function get(string $search, ?string $itemType, ?string $itemId, ?string $sorter = null, array $shareTypes = [IShare::TYPE_USER], int $limit = 10): DataResponse {
		// if enumeration/user listings are disabled, we'll receive an empty
		// result from search() – thus nothing else to do here.
		[$results,] = $this->collaboratorSearch->search($search, $shareTypes, false, $limit, 0);

		$event = new AutoCompleteEvent([
			'search' => $search,
			'results' => $results,
			'itemType' => $itemType,
			'itemId' => $itemId,
			'sorter' => $sorter,
			'shareTypes' => $shareTypes,
			'limit' => $limit,
		]);
		$this->dispatcher->dispatch(IManager::class . '::filterResults', $event);
		$results = $event->getResults();

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

		return new DataResponse($results);
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
