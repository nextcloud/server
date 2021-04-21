<?php

/**
 *
 * @copyright Copyright (c) 2018, Daniel Calviño Sánchez (danxuliu@gmail.com)
 *
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

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

class SearchContext implements Context, ActorAwareInterface {
	use ActorAware;

	/**
	 * @return Locator
	 */
	public static function searchBoxInput() {
		return Locator::forThe()->css("#header .searchbox input")->
				describedAs("Search box input in the header");
	}

	/**
	 * @return Locator
	 */
	public static function searchResults() {
		return Locator::forThe()->css("#searchresults")->
				describedAs("Search results");
	}

	/**
	 * @return Locator
	 */
	public static function searchResult($number) {
		return Locator::forThe()->xpath("//*[contains(concat(' ', normalize-space(@class), ' '), ' result ')][$number]")->
				descendantOf(self::searchResults())->
				describedAs("Search result $number");
	}

	/**
	 * @return Locator
	 */
	public static function searchResultName($number) {
		return Locator::forThe()->css(".name")->
				descendantOf(self::searchResult($number))->
				describedAs("Name for search result $number");
	}

	/**
	 * @return Locator
	 */
	public static function searchResultPath($number) {
		// Currently search results for comments misuse the ".path" class to
		// dim the user name, so "div.path" needs to be used to find the proper
		// path element.
		return Locator::forThe()->css("div.path")->
				descendantOf(self::searchResult($number))->
				describedAs("Path for search result $number");
	}

	/**
	 * @return Locator
	 */
	public static function searchResultLink($number) {
		return Locator::forThe()->css(".link")->
				descendantOf(self::searchResult($number))->
				describedAs("Link for search result $number");
	}

	/**
	 * @When I search for :query
	 */
	public function iSearchFor($query) {
		$this->actor->find(self::searchBoxInput(), 10)->setValue($query);
	}

	/**
	 * @When I open the search result :number
	 */
	public function iOpenTheSearchResult($number) {
		$this->actor->find(self::searchResultLink($number), 10)->click();
	}

	/**
	 * @Then I see that the search result :number is :name
	 */
	public function iSeeThatTheSearchResultIs($number, $name) {
		Assert::assertEquals(
				$name, $this->actor->find(self::searchResultName($number), 10)->getText());
	}

	/**
	 * @Then I see that the search result :number was found in :path
	 */
	public function iSeeThatTheSearchResultWasFoundIn($number, $path) {
		Assert::assertEquals(
				$path, $this->actor->find(self::searchResultPath($number), 10)->getText());
	}
}
