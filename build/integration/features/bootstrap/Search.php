<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\Assert;

trait Search {
	// BasicStructure trait is expected to be used in the class that uses this
	// trait.

	/**
	 * @When /^searching for "([^"]*)"$/
	 * @param string $query
	 */
	public function searchingFor(string $query) {
		$this->searchForInApp($query, '');
	}

	/**
	 * @When /^searching for "([^"]*)" in app "([^"]*)"$/
	 * @param string $query
	 * @param string $app
	 */
	public function searchingForInApp(string $query, string $app) {
		$url = '/index.php/core/search';

		$parameters[] = 'query=' . $query;
		$parameters[] = 'inApps[]=' . $app;

		$url .= '?' . implode('&', $parameters);

		$this->sendingAToWithRequesttoken('GET', $url);
	}

	/**
	 * @Then /^the list of search results has "(\d+)" results$/
	 */
	public function theListOfSearchResultsHasResults(int $count) {
		$this->theHTTPStatusCodeShouldBe(200);

		$searchResults = json_decode($this->response->getBody());

		Assert::assertEquals($count, count($searchResults));
	}

	/**
	 * @Then /^search result "(\d+)" contains$/
	 *
	 * @param int $number
	 * @param TableNode $body
	 */
	public function searchResultXContains(int $number, TableNode $body) {
		if (!($body instanceof TableNode)) {
			return;
		}

		$searchResults = json_decode($this->response->getBody(), $asAssociativeArray = true);
		$searchResult = $searchResults[$number];

		foreach ($body->getRowsHash() as $expectedField => $expectedValue) {
			if (!array_key_exists($expectedField, $searchResult)) {
				Assert::fail("$expectedField was not found in response");
			}

			Assert::assertEquals($expectedValue, $searchResult[$expectedField], "Field '$expectedField' does not match ({$searchResult[$expectedField]})");
		}
	}
}
