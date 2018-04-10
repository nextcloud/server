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
use PHPUnit\Framework\Assert;

class LDAPContext implements Context {
	use BasicStructure;

	protected $configID;

	protected $apiUrl;

	/**
	 * @Given /^the response should contain a tag "([^"]*)"$/
	 */
	public function theResponseShouldContainATag($arg1) {
		$configID = simplexml_load_string($this->response->getBody())->data[0]->$arg1;
		Assert::assertInstanceOf(SimpleXMLElement::class, $configID[0]);
	}

	/**
	 * @Given /^creating an LDAP configuration at "([^"]*)"$/
	 */
	public function creatingAnLDAPConfigurationAt($apiUrl) {
		$this->apiUrl = $apiUrl;
		$this->sendingToWith('POST', $this->apiUrl, null);
		$configElements = simplexml_load_string($this->response->getBody())->data[0]->configID;
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
		$data = simplexml_load_string($this->response->getBody())->data[0]->$tagName;
		Assert::assertEquals($expectedValue, $data[0]);
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

	/**
	 * @Given /^having a valid LDAP configuration$/
	 */
	public function havingAValidLDAPConfiguration() {
		$this->asAn('admin');
		$this->creatingAnLDAPConfigurationAt('/apps/user_ldap/api/v1/config');
		$data = new TableNode([
			['configData[ldapHost]', 'openldap'],
			['configData[ldapPort]', '389'],
			['configData[ldapBase]', 'dc=nextcloud,dc=ci'],
			['configData[ldapAgentName]', 'cn=admin,dc=nextcloud,dc=ci'],
			['configData[ldapAgentPassword]', 'admin'],
			['configData[ldapUserFilter]', '(&(objectclass=inetorgperson))'],
			['configData[ldapLoginFilter]', '(&(objectclass=inetorgperson)(uid=%uid))'],
			['configData[ldapUserDisplayName]', 'displayname'],
			['configData[ldapGroupDisplayName]', 'cn'],
			['configData[ldapEmailAttribute]', 'mail'],
			['configData[ldapConfigurationActive]', '1'],
		]);
		$this->settingTheLDAPConfigurationTo($data);
		$this->asAn('');
	}

	/**
	 * @Given /^looking up details for the first result matches expectations$/
	 * @param TableNode $expectations
	 */
	public function lookingUpDetailsForTheFirstResult(TableNode $expectations) {
		$userResultElements = simplexml_load_string($this->response->getBody())->data[0]->users[0]->element;
		$userResults = json_decode(json_encode($userResultElements), 1);
		$userId = array_shift($userResults);

		$this->sendingTo('GET', '/cloud/users/' . $userId);

		foreach($expectations->getRowsHash() as $k => $v) {
			$value = (string)simplexml_load_string($this->response->getBody())->data[0]->$k;
			PHPUnit_Framework_Assert::assertEquals($v, $value);
		}

		$backend = (string)simplexml_load_string($this->response->getBody())->data[0]->backend;
		PHPUnit_Framework_Assert::assertEquals('LDAP', $backend);
	}

	/**
	 * @Given /^modify LDAP configuration$/
	 */
	public function modifyLDAPConfiguration(TableNode $table) {
		$originalAsAn = $this->currentUser;
		$this->asAn('admin');
		$configData = $table->getRows();
		foreach($configData as &$row) {
			$row[0] = 'configData[' . $row[0] . ']';
		}
		$this->settingTheLDAPConfigurationTo(new TableNode($configData));
		$this->asAn($originalAsAn);
	}

	/**
	 * @Given /^the group result should$/
	 */
	public function theGroupResultShould(TableNode $expectations) {
		$listReturnedGroups = simplexml_load_string($this->response->getBody())->data[0]->groups[0]->element;
		$extractedGroupsArray = json_decode(json_encode($listReturnedGroups), 1);

		foreach($expectations->getRows() as $groupExpectation) {
			if((int)$groupExpectation[1] === 1) {
				PHPUnit_Framework_Assert::assertContains($groupExpectation[0], $extractedGroupsArray);
			} else {
				PHPUnit_Framework_Assert::assertNotContains($groupExpectation[0], $extractedGroupsArray);
			}
		}
	}
}
