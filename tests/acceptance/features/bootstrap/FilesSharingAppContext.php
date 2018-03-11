<?php

/**
 *
 * @copyright Copyright (c) 2017, Daniel Calviño Sánchez (danxuliu@gmail.com)
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

class FilesSharingAppContext implements Context, ActorAwareInterface {

	use ActorAware;
	use FileListAncestorSetter;

	/**
	 * @return Locator
	 */
	public static function passwordField() {
		return Locator::forThe()->field("password")->
				describedAs("Password field in Authenticate page");
	}

	/**
	 * @return Locator
	 */
	public static function authenticateButton() {
		return Locator::forThe()->id("password-submit")->
				describedAs("Authenticate button in Authenticate page");
	}

	/**
	 * @return Locator
	 */
	public static function wrongPasswordMessage() {
		return Locator::forThe()->xpath("//*[@class = 'warning' and normalize-space() = 'The password is wrong. Try again.']")->
				describedAs("Wrong password message in Authenticate page");
	}

	/**
	 * @return Locator
	 */
	public static function textPreview() {
		return Locator::forThe()->css(".text-preview")->
				describedAs("Text preview in Shared file page");
	}

	/**
	 * @When I visit the shared link I wrote down
	 */
	public function iVisitTheSharedLinkIWroteDown() {
		$this->actor->getSession()->visit($this->actor->getSharedNotebook()["shared link"]);
	}

	/**
	 * @When I authenticate with password :password
	 */
	public function iAuthenticateWithPassword($password) {
		$this->actor->find(self::passwordField(), 10)->setValue($password);
		$this->actor->find(self::authenticateButton())->click();
	}

	/**
	 * @Then I see that the current page is the Authenticate page for the shared link I wrote down
	 */
	public function iSeeThatTheCurrentPageIsTheAuthenticatePageForTheSharedLinkIWroteDown() {
		PHPUnit_Framework_Assert::assertEquals(
				$this->actor->getSharedNotebook()["shared link"] . "/authenticate",
				$this->actor->getSession()->getCurrentUrl());
	}

	/**
	 * @Then I see that the current page is the shared link I wrote down
	 */
	public function iSeeThatTheCurrentPageIsTheSharedLinkIWroteDown() {
		PHPUnit_Framework_Assert::assertEquals(
				$this->actor->getSharedNotebook()["shared link"],
				$this->actor->getSession()->getCurrentUrl());

		$this->setFileListAncestorForActor(null, $this->actor);
	}

	/**
	 * @Then I see that a wrong password for the shared file message is shown
	 */
	public function iSeeThatAWrongPasswordForTheSharedFileMessageIsShown() {
		PHPUnit_Framework_Assert::assertTrue(
				$this->actor->find(self::wrongPasswordMessage(), 10)->isVisible());
	}

	/**
	 * @Then I see that the shared file preview shows the text :text
	 */
	public function iSeeThatTheSharedFilePreviewShowsTheText($text) {
		PHPUnit_Framework_Assert::assertContains($text, $this->actor->find(self::textPreview(), 10)->getText());
	}

}
