<?php

/**
 * @copyright Copyright (c) 2017, Daniel Calviño Sánchez (danxuliu@gmail.com)
 * @copyright Copyright (c) 2018, John Molakvoæ (skjnldsv) <skjnldsv@protonmail.com>
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

class UsersSettingsContext implements Context, ActorAwareInterface {

	use ActorAware;

	/**
	 * @return Locator
	 */
	public static function newUserForm() {
		return Locator::forThe()->id("new-user")->
				describedAs("New user form in Users Settings");
	}

	/**
	 * @return Locator
	 */
	public static function userNameFieldForNewUser() {
		return Locator::forThe()->field("newusername")->
				describedAs("User name field for new user in Users Settings");
	}

	/**
	 * @return Locator
	 */
	public static function passwordFieldForNewUser() {
		return Locator::forThe()->field("newuserpassword")->
				describedAs("Password field for new user in Users Settings");
	}

	/**
	 * @return Locator
	 */
	public static function newUserButton() {
		return Locator::forThe()->id("new-user-button")->
				describedAs("New user button in Users Settings");
	}

	/**
	 * @return Locator
	 */
	public static function createNewUserButton() {
		return Locator::forThe()->xpath("//form[@id = 'new-user']//input[@type = 'submit']")->
				describedAs("Create user button in Users Settings");
	}

	/**
	 * @return Locator
	 */
	public static function rowForUser($user) {
		return Locator::forThe()->xpath("//div[@id='app-content']/div/div[normalize-space() = '$user']/..")->
				describedAs("Row for user $user in Users Settings");
	}

	/**
	 * @return Locator
	 */
	public static function passwordCellForUser($user) {
		return Locator::forThe()->css(".password")->descendantOf(self::rowForUser($user))->
				describedAs("Password cell for user $user in Users Settings");
	}

	/**
	 * @return Locator
	 */
	public static function groupsCellForUser($user) {
		return Locator::forThe()->css(".groups")->descendantOf(self::rowForUser($user))->
				describedAs("Groups cell for user $user in Users Settings");
	}

	/**
	 * @return Locator
	 */
	public static function passwordInputForUser($user) {
		return Locator::forThe()->css("input")->descendantOf(self::passwordCellForUser($user))->
				describedAs("Password input for user $user in Users Settings");
	}

	/**
	 * @return Locator
	 */
	public static function groupsInputForUser($user) {
		return Locator::forThe()->css("input")->descendantOf(self::groupsCellForUser($user))->
				describedAs("Groups input for user $user in Users Settings");
	}

	/**
	 * @return Locator
	 */
	public static function groupOptionInInputForUser($user) {
		return Locator::forThe()->css(".multiselect__option--highlight")->descendantOf(self::groupsCellForUser($user))->
				describedAs("Group option for input for user $user in Users Settings");
	}

	/**
	 * @return Locator
	 */
	public static function actionsMenuOf($user) {
		return Locator::forThe()->css(".icon-more")->descendantOf(self::rowForUser($user))->
				describedAs("Actions menu for user $user in Users Settings");
	}

	/**
	 * @return Locator
	 */
	public static function theAction($action, $user) {
		return Locator::forThe()->xpath("//button[normalize-space() = '$action']")->
				descendantOf(self::rowForUser($user))->
				describedAs("$action action for the user $user");
	}

	/**
	 * @return Locator
	 */
	public static function theColumn($column) {
		return Locator::forThe()->xpath("//div[@class='user-list-grid']//div[normalize-space() = '$column']")->
				describedAs("The $column column");
	}

	/**
	 * @When I click the New user button
	 */
	public function iClickTheNewUserButton() {
		$this->actor->find(self::newUserButton())->click();
	}

	/**
	 * @When I click the :action action in the :user actions menu
	 */
	public function iClickTheAction($action, $user) {
		$this->actor->find(self::theAction($action, $user))->click();
	}

	/**
	 * @When I open the actions menu for the user :user
	 */
	public function iOpenTheActionsMenuOf($user) {
		$this->actor->find(self::actionsMenuOf($user))->click();
	}

	/**
	 * @When I create user :user with password :password
	 */
	public function iCreateUserWithPassword($user, $password) {
		$this->actor->find(self::userNameFieldForNewUser(), 10)->setValue($user);
		$this->actor->find(self::passwordFieldForNewUser())->setValue($password);
		$this->actor->find(self::createNewUserButton())->click();
	}

	/**
	 * @When I set the password for :user to :password
	 */
	public function iSetThePasswordForUserTo($user, $password) {
		$this->actor->find(self::passwordInputForUser($user), 2)->setValue($password . "\r");
	}

	/**
	 * @When I assign the user :user to the group :group
	 */
	public function iAssignTheUserToTheGroup($user, $group) {
		$this->actor->find(self::groupsInputForUser($user))->setValue($group);
		$this->actor->find(self::groupOptionInInputForUser($user))->click();
	}

	/**
	 * @Then I see that the list of users contains the user :user
	 */
	public function iSeeThatTheListOfUsersContainsTheUser($user) {
		WaitFor::elementToBeEventuallyShown($this->actor, self::rowForUser($user));
	}

	/**
	 * @Then I see that the list of users does not contains the user :user
	 */
	public function iSeeThatTheListOfUsersDoesNotContainsTheUser($user) {
		WaitFor::elementToBeEventuallyNotShown($this->actor, self::rowForUser($user));
	}

	/**
	 * @Then I see that the new user form is shown
	 */
	public function iSeeThatTheNewUserFormIsShown() {
		PHPUnit_Framework_Assert::assertTrue(
				$this->actor->find(self::newUserForm(), 10)->isVisible());
	}

	/**
	 * @Then I see that the :action action in the :user actions menu is shown
	 */
	public function iSeeTheAction($action, $user) {
		PHPUnit_Framework_Assert::assertTrue(
				$this->actor->find(self::theAction($action, $user), 10)->isVisible());
	}

	/**
	 * @Then I see that the :column column is shown
	 */
	public function iSeeThatTheColumnIsShown($column) {
		PHPUnit_Framework_Assert::assertTrue(
				$this->actor->find(self::theColumn($column), 10)->isVisible());
	}

}
