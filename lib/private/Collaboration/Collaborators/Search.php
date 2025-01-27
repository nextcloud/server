<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Collaboration\Collaborators;

use OCP\Collaboration\Collaborators\ISearch;
use OCP\Collaboration\Collaborators\ISearchPlugin;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\IContainer;
use OCP\Share;

class Search implements ISearch {
	protected array $pluginList = [];

	public function __construct(
		private IContainer $container,
	) {
	}

	/**
	 * @param string $search
	 * @param bool $lookup
	 * @param int|null $limit
	 * @param int|null $offset
	 * @throws \OCP\AppFramework\QueryException
	 */
	public function search($search, array $shareTypes, $lookup, $limit, $offset): array {
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
				$searchPlugin = $this->container->resolve($plugin);
				$hasMoreResults = $searchPlugin->search($search, $limit, $offset, $searchResult) || $hasMoreResults;
			}
		}

		// Get from lookup server, not a separate share type
		if ($lookup) {
			$searchPlugin = $this->container->resolve(LookupPlugin::class);
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

		return [$searchResult->asArray(), $hasMoreResults];
	}

	public function registerPlugin(array $pluginInfo): void {
		$shareType = constant(Share::class . '::' . $pluginInfo['shareType']);
		if ($shareType === null) {
			throw new \InvalidArgumentException('Provided ShareType is invalid');
		}
		$this->pluginList[$shareType][] = $pluginInfo['class'];
	}

	/**
	 * Removes all mail shares where a remote share with the same label exists.
	 * Remote shares are considered "better" than mail shares, so we hide the "worse" shares in case both are possible.
	 */
	protected function dropMailSharesWhereRemoteShareIsPossible(ISearchResult $searchResult): void {
		$allResults = $searchResult->asArray();

		$emailType = new SearchResultType('emails');
		$remoteType = new SearchResultType('remotes');

		if (!isset($allResults[$remoteType->getLabel()])
			|| !isset($allResults[$emailType->getLabel()])) {
			return;
		}

		$mails = [];
		foreach ($allResults[$emailType->getLabel()] as $mailRow) {
			$mails[] = $mailRow['value']['shareWith'];
		}

		foreach ($allResults[$remoteType->getLabel()] as $remoteRow) {
			if (in_array($remoteRow['value']['shareWith'], $mails, true)) {
				$searchResult->removeCollaboratorResult($emailType, $remoteRow['value']['shareWith']);
			}
		}
	}
}
