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
use PHPUnit\Framework\Assert;

class PublicShareContext implements Context, ActorAwareInterface {
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
	public static function shareMenuButton() {
		return Locator::forThe()->id("header-actions-toggle")->
				describedAs("Share menu button in Shared file page");
	}

	/**
	 * @return Locator
	 */
	public static function shareMenu() {
		return Locator::forThe()->id("header-actions-menu")->
				describedAs("Share menu in Shared file page");
	}

	/**
	 * @return Locator
	 */
	public static function downloadItemInShareMenu() {
		return Locator::forThe()->id("download")->
				descendantOf(self::shareMenu())->
				describedAs("Download item in Share menu in Shared file page");
	}

	/**
	 * @return Locator
	 */
	public static function directLinkItemInShareMenu() {
		return Locator::forThe()->id("directLink-container")->
				descendantOf(self::shareMenu())->
				describedAs("Direct link item in Share menu in Shared file page");
	}

	/**
	 * @return Locator
	 */
	public static function saveItemInShareMenu() {
		return Locator::forThe()->id("save-external-share")->
				descendantOf(self::shareMenu())->
				describedAs("Save item in Share menu in Shared file page");
	}

	/**
	 * @return Locator
	 */
	public static function textPreview() {
		return Locator::forThe()->css(".text-preview")->
				describedAs("Text preview in Shared file page");
	}

	/**
	 * @return Locator
	 */
	public static function downloadButton() {
		return Locator::forThe()->id("downloadFile")->
				describedAs("Download button in Shared file page");
	}

	/**
	 * @When I visit the shared link I wrote down
	 */
	public function iVisitTheSharedLinkIWroteDown() {
		$this->actor->getSession()->visit($this->actor->getSharedNotebook()["shared link"]);
	}

	/**
	 * @When I visit the direct download shared link I wrote down
	 */
	public function iVisitTheDirectDownloadSharedLinkIWroteDown() {
		$this->actor->getSession()->visit($this->actor->getSharedNotebook()["shared link"] . "/download");
	}

	/**
	 * @When I authenticate with password :password
	 */
	public function iAuthenticateWithPassword($password) {
		$this->actor->find(self::passwordField(), 10)->setValue($password);
		$this->actor->find(self::authenticateButton())->click();
	}

	/**
	 * @When I open the Share menu
	 */
	public function iOpenTheShareMenu() {
		$this->actor->find(self::shareMenuButton(), 10)->click();
	}

	/**
	 * @Then I see that the current page is the Authenticate page for the shared link I wrote down
	 */
	public function iSeeThatTheCurrentPageIsTheAuthenticatePageForTheSharedLinkIWroteDown() {
		Assert::assertEquals(
				$this->actor->getSharedNotebook()["shared link"] . "/authenticate/showShare",
				$this->actor->getSession()->getCurrentUrl());
	}

	/**
	 * @Then I see that the current page is the Authenticate page for the direct download shared link I wrote down
	 */
	public function iSeeThatTheCurrentPageIsTheAuthenticatePageForTheDirectDownloadSharedLinkIWroteDown() {
		Assert::assertEquals(
				$this->actor->getSharedNotebook()["shared link"] . "/authenticate/downloadShare",
				$this->actor->getSession()->getCurrentUrl());
	}

	/**
	 * @Then I see that the current page is the shared link I wrote down
	 */
	public function iSeeThatTheCurrentPageIsTheSharedLinkIWroteDown() {
		Assert::assertEquals(
				$this->actor->getSharedNotebook()["shared link"],
				$this->actor->getSession()->getCurrentUrl());

		$this->setFileListAncestorForActor(null, $this->actor);
	}

	/**
	 * @Then I see that the current page is the direct download shared link I wrote down
	 */
	public function iSeeThatTheCurrentPageIsTheDirectDownloadSharedLinkIWroteDown() {
		Assert::assertEquals(
				$this->actor->getSharedNotebook()["shared link"] . "/download",
				$this->actor->getSession()->getCurrentUrl());
	}

	/**
	 * @Then I see that a wrong password for the shared file message is shown
	 */
	public function iSeeThatAWrongPasswordForTheSharedFileMessageIsShown() {
		Assert::assertTrue(
				$this->actor->find(self::wrongPasswordMessage(), 10)->isVisible());
	}

	/**
	 * @Then I see that the Share menu is shown
	 */
	public function iSeeThatTheShareMenuIsShown() {
		// Unlike other menus, the Share menu is always present in the DOM, so
		// the element could be found when it was no made visible yet due to the
		// command not having been processed by the browser.
		if (!WaitFor::elementToBeEventuallyShown(
				$this->actor, self::shareMenu(), $timeout = 10 * $this->actor->getFindTimeoutMultiplier())) {
			Assert::fail("The Share menu is not visible yet after $timeout seconds");
		}

		// The acceptance tests are run in a window wider than the mobile breakpoint, so the
		// download item should not be shown in the menu (although it will be in
		// the DOM).
		Assert::assertFalse(
				$this->actor->find(self::downloadItemInShareMenu())->isVisible(),
				"Download item in share menu is visible");
		Assert::assertTrue(
				$this->actor->find(self::directLinkItemInShareMenu())->isVisible(),
				"Direct link item in share menu is not visible");
		Assert::assertTrue(
				$this->actor->find(self::saveItemInShareMenu())->isVisible(),
				"Save item in share menu is not visible");
	}

	/**
	 * @Then I see that the Share menu button is not shown
	 */
	public function iSeeThatTheShareMenuButtonIsNotShown() {
		try {
			Assert::assertFalse(
					$this->actor->find(self::shareMenuButton())->isVisible());
		} catch (NoSuchElementException $exception) {
		}
	}

	/**
	 * @Then I see that the shared file preview shows the text :text
	 */
	public function iSeeThatTheSharedFilePreviewShowsTheText($text) {
		Assert::assertStringContainsString($text, $this->actor->find(self::textPreview(), 10)->getText());
	}

	/**
	 * @Then I see that the download button is shown
	 */
	public function iSeeThatTheDownloadButtonIsShown() {
		if (!WaitFor::elementToBeEventuallyShown(
				$this->actor, self::downloadButton(), $timeout = 10 * $this->actor->getFindTimeoutMultiplier())) {
			Assert::fail("The download button is not visible yet after $timeout seconds");
		}
	}

	/**
	 * @Then I see that the download button is not shown
	 */
	public function iSeeThatTheDownloadButtonIsNotShown() {
		try {
			Assert::assertFalse(
					$this->actor->find(self::downloadButton())->isVisible());
		} catch (NoSuchElementException $exception) {
		}
	}
}
