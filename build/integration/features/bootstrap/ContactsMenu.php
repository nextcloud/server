<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Step\Then;
use Behat\Step\When;
use PHPUnit\Framework\Assert;

trait ContactsMenu {
	// BasicStructure trait is expected to be used in the class that uses this
	// trait.
	/**
	 * @param string $filter
	 */
	#[When('/^searching for contacts matching with "([^"]*)"$/')]
	public function searchingForContactsMatchingWith(string $filter) {
		$url = '/index.php/contactsmenu/contacts';

		$parameters[] = 'filter=' . $filter;

		$url .= '?' . implode('&', $parameters);

		$this->sendingAToWithRequesttoken('POST', $url);
	}

	#[Then('/^the list of searched contacts has "(\d+)" contacts$/')]
	public function theListOfSearchedContactsHasContacts(int $count) {
		$this->theHTTPStatusCodeShouldBe(200);

		$searchedContacts = json_decode($this->response->getBody(), $asAssociativeArray = true)['contacts'];

		Assert::assertEquals($count, count($searchedContacts));
	}

	/**
	 *
	 * @param int $index
	 * @param string $expectedName
	 */
	#[Then('/^searched contact "(\d+)" is named "([^"]*)"$/')]
	public function searchedContactXIsNamed(int $index, string $expectedName) {
		$searchedContacts = json_decode($this->response->getBody(), $asAssociativeArray = true)['contacts'];
		$searchedContact = $searchedContacts[$index];

		Assert::assertEquals($expectedName, $searchedContact['fullName']);
	}
}
