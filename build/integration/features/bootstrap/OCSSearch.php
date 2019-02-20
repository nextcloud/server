<?php

/**
 *
 * @copyright Copyright (c) 2019, Daniel Calviño Sánchez (danxuliu@gmail.com)
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

use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\Assert;

trait OCSSearch {

	// BasicStructure trait is expected to be used in the class that uses this
	// trait.

	/**
	 * @When /^user "([^"]*)" OCS searches for "([^"]*)" in app "([^"]*)"$/
	 *
	 * @param string $user
	 * @param string $query
	 * @param string $app
	 */
	public function userOcsSearchesForInApp(string $user, string $query, string $app) {
		$this->currentUser = $user;

		$url = '/core/search';

		$parameters[] = 'query=' . $query;
		$parameters[] = 'inApps[]=' . $app;

		$url .= '?' . implode('&', $parameters);

		$this->sendingTo('GET', $url);
	}

	/**
	 * @Then /^the list of OCS search results has "(\d+)" results$/
	 */
	public function theListOfOcsSearchResultsHasResults(int $count) {
		$this->theHTTPStatusCodeShouldBe('200');
		$this->theOCSStatusCodeShouldBe('100');

		$searchResults = simplexml_load_string($this->response->getBody())->data[0];

		Assert::assertEquals($count, count($searchResults->element));
	}

	/**
	 * @Then /^OCS search result "(\d+)" contains$/
	 *
	 * @param int $number
	 * @param TableNode $body
	 */
	public function ocsSearchResultXContains(int $number, TableNode $body) {
		if (!($body instanceof TableNode)) {
			return;
		}

		$searchResults = simplexml_load_string($this->response->getBody())->data[0];
		$searchResult = $searchResults->element[$number];

		foreach ($body->getRowsHash() as $expectedField => $expectedValue) {
			if (!array_key_exists($expectedField, $searchResult)) {
				Assert::fail("$expectedField was not found in response");
			}

			Assert::assertEquals($expectedValue, (string)$searchResult->$expectedField, "Field '$expectedField' does not match (" . $searchResult->$expectedField . ")");
		}
	}

}
