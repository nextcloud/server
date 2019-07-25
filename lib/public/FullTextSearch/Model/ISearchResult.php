<?php
declare(strict_types=1);


/**
 * FullTextSearch - Full text search framework for Nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018
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
 * improve the Search Results with detailed informations.
 *
 * Finally, the SearchResult is returned to the original search request.
 *
 * @since 15.0.0
 *
 * @package OCP\FullTextSearch\Model
 */
interface ISearchResult {


	/**
	 * Get the original SearchRequest.
	 *
	 * @return ISearchRequest
	 * @since 15.0.0
	 *
	 * @see ISearchRequest
	 *
	 */
	public function getRequest(): ISearchRequest;

	/**
	 * Get the targeted Content Provider.
	 *
	 * @return IFullTextSearchProvider
	 * @since 15.0.0
	 *
	 */
	public function getProvider(): IFullTextSearchProvider;


	/**
	 * Add an IIndexDocument as one of the result of the search request.
	 *
	 * @param IIndexDocument $document
	 *
	 * @return ISearchResult
	 * @since 15.0.0
	 *
	 */
	public function addDocument(IIndexDocument $document): ISearchResult;

	/**
	 * Returns all result of the search request, in an array of IIndexDocument.
	 *
	 * @return IIndexDocument[]
	 * @since 15.0.0
	 *
	 */
	public function getDocuments(): array;

	/**
	 * Set an array of IIndexDocument as the result of the search request.
	 *
	 * @param IIndexDocument[] $documents
	 *
	 * @return ISearchResult
	 * @since 15.0.0
	 *
	 */
	public function setDocuments(array $documents): ISearchResult;



	
	public function addInfo(string $k, string $value): ISearchResult;

	public function getInfo(string $k): string;

	public function getInfosAll(): array;


	/**
	 * Add an aggregation to the result.
	 *
	 * @param string $category
	 * @param string $value
	 * @param int $count
	 *
	 * @return ISearchResult
	 * @since 15.0.0
	 *
	 */
	public function addAggregation(string $category, string $value, int $count): ISearchResult;

	/**
	 * Get all aggregations.
	 *
	 * @param string $category
	 *
	 * @return array
	 * @since 15.0.0
	 *
	 */
	public function getAggregations(string $category): array;


	/**
	 * Set the raw result of the request.
	 *
	 * @param string $result
	 *
	 * @return ISearchResult
	 * @since 15.0.0
	 *
	 */
	public function setRawResult(string $result): ISearchResult;


	/**
	 * Set the total number of results for the search request.
	 * Used by pagination.
	 *
	 * @param int $total
	 *
	 * @return ISearchResult
	 * @since 15.0.0
	 *
	 */
	public function setTotal(int $total): ISearchResult;


	/**
	 * Set the top score for the search request.
	 *
	 * @param int $score
	 *
	 * @return ISearchResult
	 * @since 15.0.0
	 *
	 */
	public function setMaxScore(int $score): ISearchResult;


	/**
	 * Set the time spent by the request to perform the search.
	 *
	 * @param int $time
	 *
	 * @return ISearchResult
	 * @since 15.0.0
	 *
	 */
	public function setTime(int $time): ISearchResult;


	/**
	 * Set to true if the request timed out.
	 *
	 * @param bool $timedOut
	 *
	 * @return ISearchResult
	 * @since 15.0.0
	 *
	 */
	public function setTimedOut(bool $timedOut): ISearchResult;

}

