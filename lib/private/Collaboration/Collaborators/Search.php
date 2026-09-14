<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Collaboration\Collaborators;

use OCP\Collaboration\AutoComplete\AutoCompleteFilterEvent;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\Collaboration\Collaborators\ISearchPlugin;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IContainer;
use OCP\Share\IShare;

class Search implements ISearch {
	/** @var array<IShare::TYPE_*, list<class-string<ISearchPlugin>>> $pluginList */
	protected array $pluginList = [];

	public function __construct(
		private readonly IContainer $container,
		private readonly IEventDispatcher $eventDispatcher,
	) {
	}

	#[\Override]
	public function search(string $search, array $shareTypes, bool $lookup, int $limit, int $offset): array {
		[$results, $more] = $this->filteredSearch($search, $shareTypes, $lookup, null, null, $limit, $offset, false);
		return [$results, $more];
	}

	#[\Override]
	public function filteredSearch(string $search, array $shareTypes, bool $lookup, ?string $itemType, ?string $itemId, int $limit, int $offset, bool $sendFilterEvent = true): array {
		$hasMoreResults = false;

		// Trim leading and trailing whitespace characters, e.g. when query is copy-pasted
		$search = trim($search);

		/** @var ISearchResult $searchResult */
		$searchResult = $this->container->resolve(SearchResult::class);

		foreach ($shareTypes as $type) {
			if (!isset($this->pluginList[$type])) {
				continue;
			}
			foreach ($this->pluginList[$type] as $plugin) {
				/** @var ISearchPlugin $searchPlugin */
				$searchPlugin = $this->container->get($plugin);
				if ($searchPlugin instanceof UserPlugin && $lookup) {
					// we are in GlobalScale, we ignore local accounts and prefer the result from lookup
					continue;
				}
				$hasMoreResults = $searchPlugin->search($search, $limit, $offset, $searchResult) || $hasMoreResults;
			}
		}

		// Get from lookup server, not a separate share type
		if ($lookup) {
			$searchPlugin = $this->container->get(LookupPlugin::class);
			$hasMoreResults = $searchPlugin->search($search, $limit, $offset, $searchResult) || $hasMoreResults;
		}

		// sanitizing, could go into the plugins as well

		// if we have an exact match, either for the federated cloud id or for the
		// email address, we only return the exact match. It is highly unlikely
		// that the exact same email address and federated cloud id exists
		$emailType = new SearchResultType('emails');
		$remoteType = new SearchResultType('remotes');
		if ($searchResult->hasExactIdMatch($emailType) && !$searchResult->hasExactIdMatch($remoteType)) {
			$searchResult->unsetResult($remoteType);
		} elseif (!$searchResult->hasExactIdMatch($emailType) && $searchResult->hasExactIdMatch($remoteType)) {
			$searchResult->unsetResult($emailType);
		}

		$this->dropMailSharesWhereRemoteShareIsPossible($searchResult);

		// if we have an exact local user match with an email-a-like query,
		// there is no need to show the remote and email matches.
		$userType = new SearchResultType('users');
		if (str_contains($search, '@') && $searchResult->hasExactIdMatch($userType)) {
			$searchResult->unsetResult($remoteType);
			$searchResult->unsetResult($emailType);
		}

		$results = $searchResult->asArray();
		if ($sendFilterEvent) {
			$event = new AutoCompleteFilterEvent(
				$results,
				$search,
				$itemType,
				$itemId,
				null,
				$shareTypes,
				$limit,
			);
			$this->eventDispatcher->dispatchTyped($event);
			$results = $event->getResults();
		}

		return [$results, $hasMoreResults];
	}

	#[\Override]
	public function registerPlugin(array $pluginInfo): void {
		/** @psalm-suppress InvalidScalarArgument For legacy reasons */
		if (str_starts_with($pluginInfo['shareType'], 'SHARE_')) {
			$shareType = constant(IShare::class . '::' . substr($pluginInfo['shareType'], strlen('SHARE_')));
		} else {
			$shareType = $pluginInfo['shareType'];
		}
		if ($shareType === null) {
			throw new \InvalidArgumentException('Provided ShareType is invalid');
		}
		$this->pluginList[$shareType][] = $pluginInfo['class'];
	}

	protected function dropMailSharesWhereRemoteShareIsPossible(ISearchResult $searchResult): void {
		$allResults = $searchResult->asArray();

		$emailType = new SearchResultType('emails');
		$emailLabel = $emailType->getLabel();
		$emailEntries = array_merge(
			$allResults['exact'][$emailLabel] ?? [],
			$allResults[$emailLabel] ?? []
		);
		if ($emailEntries === []) {
			return;
		}

		$mailIdMap = [];
		foreach ($emailEntries as $mailRow) {
			// sure, array_reduce looks nicer, but foreach needs less resources and is faster
			if (!isset($mailRow['uuid'])) {
				continue;
			}
			$mailIdMap[$mailRow['uuid']] = $mailRow['value']['shareWith'];
		}

		$remoteType = new SearchResultType('remotes');
		if (isset($allResults[$remoteType->getLabel()])) {
			foreach ($allResults[$remoteType->getLabel()] as $resultRow) {
				if (!isset($resultRow['uuid'])) {
					continue;
				}
				if (isset($mailIdMap[$resultRow['uuid']])) {
					$searchResult->removeCollaboratorResult($emailType, $mailIdMap[$resultRow['uuid']]);
				}
			}
		}

		$lookupType = new SearchResultType('lookup');
		if (isset($allResults[$lookupType->getLabel()])) {
			foreach ($allResults[$lookupType->getLabel()] as $resultRow) {
				$userid = $resultRow['extra']['userid']['value'] ?? null;
				if ($userid === null) {
					continue;
				}
				if (isset($mailIdMap[$userid])) {
					$searchResult->removeCollaboratorResult($emailType, $mailIdMap[$userid]);
				}
			}
		}
	}
}
