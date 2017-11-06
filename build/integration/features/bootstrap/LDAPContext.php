<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
use Behat\Gherkin\Node\TableNode;

class LDAPContext implements Context {
	use BasicStructure;

	protected $configID;

	protected $apiUrl;

	/**
	 * @Given /^the response should contain a tag "([^"]*)"$/
	 */
	public function theResponseShouldContainATag($arg1) {
		$configID = $this->response->xml()->data[0]->$arg1;
		PHPUnit_Framework_Assert::assertInstanceOf(SimpleXMLElement::class, $configID[0]);
	}

	/**
	 * @Given /^creating an LDAP configuration at "([^"]*)"$/
	 */
	public function creatingAnLDAPConfigurationAt($apiUrl) {
		$this->apiUrl = $apiUrl;
		$this->sendingToWith('POST', $this->apiUrl, null);
		$configElements = $this->response->xml()->data[0]->configID;
		$this->configID = $configElements[0];
	}

	/**
	 * @When /^deleting the LDAP configuration$/
	 */
	public function deletingTheLDAPConfiguration() {
		$this->sendingToWith('DELETE', $this->apiUrl . '/' . $this->configID, null);
	}

	/**
	 * @Given /^the response should contain a tag "([^"]*)" with value "([^"]*)"$/
	 */
	public function theResponseShouldContainATagWithValue($tagName, $expectedValue) {
		$data = $this->response->xml()->data[0]->$tagName;
		PHPUnit_Framework_Assert::assertEquals($expectedValue, $data[0]);
	}

	/**
	 * @When /^getting the LDAP configuration with showPassword "([^"]*)"$/
	 */
	public function gettingTheLDAPConfigurationWithShowPassword($showPassword) {
		$this->sendingToWith(
			'GET',
			$this->apiUrl . '/' . $this->configID . '?showPassword=' . $showPassword,
			null
		);
	}

	/**
	 * @Given /^setting the LDAP configuration to$/
	 */
	public function settingTheLDAPConfigurationTo(TableNode $configData) {
		$this->sendingToWith('PUT', $this->apiUrl . '/' . $this->configID, $configData);
	}
}
