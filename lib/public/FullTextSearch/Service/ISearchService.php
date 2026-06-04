<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\FullTextSearch\Service;

use OCP\FullTextSearch\Model\ISearchRequest;
use OCP\FullTextSearch\Model\ISearchResult;

/**
 * Interface ISearchService
 *
 * @since 15.0.0
 *
 */
interface ISearchService {
	/**
	 * generate a search request, based on an array:
	 *
	 * $request =
	 *   [
	 *        'providers' =>    (string/array) 'all'
	 *        'author' =>       (string) owner of the document.
	 *        'search' =>       (string) search string,
	 *        'size' =>         (int) number of items to be return
	 *        'page' =>         (int) page
	 *        'parts' =>        (array) parts of document to search within,
	 *        'options' =       (array) search options,
	 *        'tags'     =>     (array) tags,
	 *        'metatags' =>     (array) metatags,
	 *        'subtags'  =>     (array) subtags
	 *   ]
	 *
	 * 'providers' can be an array of providerIds
	 *
	 * @since 15.0.0
	 *
	 * @param array $request
	 *
	 * @return ISearchRequest
	 */
	public function generateSearchRequest(array $request): ISearchRequest;


	/**
	 * Search documents
	 *
	 * @since 15.0.0
	 *
	 * @param string $userId
	 * @param ISearchRequest $searchRequest
	 *
	 * @return ISearchResult[]
	 */
	public function search(string $userId, ISearchRequest $searchRequest): array;
}
