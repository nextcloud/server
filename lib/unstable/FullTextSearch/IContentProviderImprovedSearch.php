<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\FullTextSearch;

use OCP\FullTextSearch\Model\ISearchRequest;
use OCP\FullTextSearch\Model\ISearchResult;
use OCP\FullTextSearch\Model\ISearchTemplate;

/**
 * implementing this interface to your content provider
 * will complete it with search related features
 */
interface IContentProviderImprovedSearch {
	public function getSearchTemplate(): ?ISearchTemplate;
	public function improveSearchRequest(ISearchRequest $searchRequest): void;
	public function improveSearchResult(ISearchResult $searchResult): void;
}
