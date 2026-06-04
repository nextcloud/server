<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\FullTextSearch\Model;

use OCP\FullTextSearch\IFullTextSearchProvider;

/**
 * Interface ISearchResult
 *
 * When a search request is initiated, FullTextSearch will create a SearchResult
 * object, based on this interface, containing the SearchRequest and the targeted
 * Content Provider.
 *
 * The object will be passed to the Search Platform, which will proceed to the
 * search and fill the SearchResult object with results.
 *
 * Then, the object will be passed to the targeted Content Provider that will
 * improve the Search Results with detailed information.
 *
 * Finally, the SearchResult is returned to the original search request.
 *
 * @since 15.0.0
 *
 */
interface ISearchResult {
	/**
	 * Get the original SearchRequest.
	 *
	 * @see ISearchRequest
	 *
	 * @since 15.0.0
	 *
	 * @return ISearchRequest
	 */
	public function getRequest(): ISearchRequest;

	/**
	 * Get the targeted Content Provider.
	 *
	 * @since 15.0.0
	 *
	 * @return IFullTextSearchProvider
	 */
	public function getProvider(): IFullTextSearchProvider;


	/**
	 * Add an IIndexDocument as one of the result of the search request.
	 *
	 * @since 15.0.0
	 *
	 * @param IIndexDocument $document
	 *
	 * @return ISearchResult
	 */
	public function addDocument(IIndexDocument $document): ISearchResult;

	/**
	 * Returns all result of the search request, in an array of IIndexDocument.
	 *
	 * @since 15.0.0
	 *
	 * @return IIndexDocument[]
	 */
	public function getDocuments(): array;

	/**
	 * Set an array of IIndexDocument as the result of the search request.
	 *
	 * @since 15.0.0
	 *
	 * @param IIndexDocument[] $documents
	 *
	 * @return ISearchResult
	 */
	public function setDocuments(array $documents): ISearchResult;


	/**
	 * Add an aggregation to the result.
	 *
	 * @since 15.0.0
	 *
	 * @param string $category
	 * @param string $value
	 * @param int $count
	 *
	 * @return ISearchResult
	 */
	public function addAggregation(string $category, string $value, int $count): ISearchResult;

	/**
	 * Get all aggregations.
	 *
	 * @since 15.0.0
	 *
	 * @param string $category
	 *
	 * @return array
	 */
	public function getAggregations(string $category): array;


	/**
	 * Set the raw result of the request.
	 *
	 * @since 15.0.0
	 *
	 * @param string $result
	 *
	 * @return ISearchResult
	 */
	public function setRawResult(string $result): ISearchResult;


	/**
	 * Set the total number of results for the search request.
	 * Used by pagination.
	 *
	 * @since 15.0.0
	 *
	 * @param int $total
	 *
	 * @return ISearchResult
	 */
	public function setTotal(int $total): ISearchResult;


	/**
	 * Set the top score for the search request.
	 *
	 * @since 15.0.0
	 *
	 * @param int $score
	 *
	 * @return ISearchResult
	 */
	public function setMaxScore(int $score): ISearchResult;


	/**
	 * Set the time spent by the request to perform the search.
	 *
	 * @since 15.0.0
	 *
	 * @param int $time
	 *
	 * @return ISearchResult
	 */
	public function setTime(int $time): ISearchResult;


	/**
	 * Set to true if the request timed out.
	 *
	 * @since 15.0.0
	 *
	 * @param bool $timedOut
	 *
	 * @return ISearchResult
	 */
	public function setTimedOut(bool $timedOut): ISearchResult;
}
