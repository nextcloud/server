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

class FilesAppContext implements Context, ActorAwareInterface {

	use ActorAware;

	/**
	 * @return Locator
	 */
	public static function currentSectionMainView() {
		return Locator::forThe()->xpath("//*[starts-with(@id, 'app-content-')  and not(contains(concat(' ', normalize-space(@class), ' '), ' hidden '))]")->
				describedAs("Current section main view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function currentSectionDetailsView() {
		return Locator::forThe()->xpath("/preceding-sibling::*[position() = 1 and @id = 'app-sidebar']")->
				descendantOf(self::currentSectionMainView())->
				describedAs("Current section details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function shareLinkCheckbox() {
		return Locator::forThe()->content("Share link")->descendantOf(self::currentSectionDetailsView())->
				describedAs("Share link checkbox in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function shareLinkField() {
		return Locator::forThe()->css(".linkText")->descendantOf(self::currentSectionDetailsView())->
				describedAs("Share link field in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function passwordProtectCheckbox() {
		return Locator::forThe()->content("Password protect")->descendantOf(self::currentSectionDetailsView())->
				describedAs("Password protect checkbox in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function passwordProtectField() {
		return Locator::forThe()->css(".linkPassText")->descendantOf(self::currentSectionDetailsView())->
				describedAs("Password protect field in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function passwordProtectWorkingIcon() {
		return Locator::forThe()->css(".linkPass .icon-loading-small")->descendantOf(self::currentSectionDetailsView())->
				describedAs("Password protect working icon in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function rowForFile($fileName) {
		return Locator::forThe()->xpath("//*[@id = 'fileList']//span[contains(concat(' ', normalize-space(@class), ' '), ' nametext ') and normalize-space() = '$fileName']/ancestor::tr")->
				descendantOf(self::currentSectionMainView())->
				describedAs("Row for file $fileName in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function shareActionForFile($fileName) {
		return Locator::forThe()->css(".action-share")->descendantOf(self::rowForFile($fileName))->
				describedAs("Share action for file $fileName in Files app");
	}

	/**
	 * @Given I share the link for :fileName
	 */
	public function iShareTheLinkFor($fileName) {
		$this->actor->find(self::shareActionForFile($fileName), 10)->click();

		$this->actor->find(self::shareLinkCheckbox(), 5)->click();
	}

	/**
	 * @Given I write down the shared link
	 */
	public function iWriteDownTheSharedLink() {
		$this->actor->getSharedNotebook()["shared link"] = $this->actor->find(self::shareLinkField(), 10)->getValue();
	}

	/**
	 * @When I protect the shared link with the password :password
	 */
	public function iProtectTheSharedLinkWithThePassword($password) {
		$this->actor->find(self::passwordProtectCheckbox(), 10)->click();

		$this->actor->find(self::passwordProtectField(), 2)->setValue($password . "\r");
	}

	/**
	 * @Then I see that the current page is the Files app
	 */
	public function iSeeThatTheCurrentPageIsTheFilesApp() {
		PHPUnit_Framework_Assert::assertStringStartsWith(
				$this->actor->locatePath("/apps/files/"),
				$this->actor->getSession()->getCurrentUrl());
	}

	/**
	 * @Then I see that the working icon for password protect is shown
	 */
	public function iSeeThatTheWorkingIconForPasswordProtectIsShown() {
		PHPUnit_Framework_Assert::assertNotNull($this->actor->find(self::passwordProtectWorkingIcon(), 10));
	}

	/**
	 * @Then I see that the working icon for password protect is eventually not shown
	 */
	public function iSeeThatTheWorkingIconForPasswordProtectIsEventuallyNotShown() {
		$timeout = 10;
		$timeoutStep = 1;

		$actor = $this->actor;
		$passwordProtectWorkingIcon = self::passwordProtectWorkingIcon();

		$workingIconNotFoundCallback = function() use ($actor, $passwordProtectWorkingIcon) {
			try {
				return !$actor->find($passwordProtectWorkingIcon)->isVisible();
			} catch (NoSuchElementException $exception) {
				return true;
			}
		};
		if (!Utils::waitFor($workingIconNotFoundCallback, $timeout, $timeoutStep)) {
			PHPUnit_Framework_Assert::fail("The working icon for password protect is still shown after $timeout seconds");
		}
	}

	/**
	 * @Given I share the link for :fileName protected by the password :password
	 */
	public function iShareTheLinkForProtectedByThePassword($fileName, $password) {
		$this->iShareTheLinkFor($fileName);
		$this->iProtectTheSharedLinkWithThePassword($password);
		$this->iSeeThatTheWorkingIconForPasswordProtectIsShown();
		$this->iSeeThatTheWorkingIconForPasswordProtectIsEventuallyNotShown();
	}

}
