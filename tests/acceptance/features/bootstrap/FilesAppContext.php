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
	 * @return array
	 */
	public static function sections() {
		return [ "All files" => "files",
				 "Recent" => "recent",
				 "Favorites" => "favorites",
				 "Shared with you" => "sharingin",
				 "Shared with others" => "sharingout",
				 "Shared by link" => "sharinglinks",
				 "Tags" => "systemtagsfilter",
				 "Deleted files" => "trashbin" ];
	}

	/**
	 * @return Locator
	 */
	public static function appNavigation() {
		return Locator::forThe()->id("app-navigation")->
				describedAs("App navigation");
	}

	/**
	 * @return Locator
	 */
	public static function appNavigationSectionItemFor($sectionText) {
		return Locator::forThe()->xpath("//li[normalize-space() = '$sectionText']")->
				descendantOf(self::appNavigation())->
				describedAs($sectionText . " section item in App Navigation");
	}

	/**
	 * @return Locator
	 */
	public static function appNavigationCurrentSectionItem() {
		return Locator::forThe()->css(".active")->descendantOf(self::appNavigation())->
				describedAs("Current section item in App Navigation");
	}

	/**
	 * @return Locator
	 */
	public static function mainViewForSection($section) {
		$sectionId = self::sections()[$section];

		return Locator::forThe()->id("app-content-$sectionId")->
				describedAs("Main view for section $section in Files app");
	}

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
	public static function detailsViewForSection($section) {
		return Locator::forThe()->xpath("/preceding-sibling::*[position() = 1 and @id = 'app-sidebar']")->
				descendantOf(self::mainViewForSection($section))->
				describedAs("Details view for section $section in Files app");
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
	public static function favoriteActionForFile($fileName) {
		return Locator::forThe()->css(".action-favorite")->descendantOf(self::rowForFile($fileName))->
				describedAs("Favorite action for file $fileName in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function favoritedStateIconForFile($fileName) {
		return Locator::forThe()->content("Favorited")->descendantOf(self::favoriteActionForFile($fileName))->
				describedAs("Favorited state icon for file $fileName in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function mainLinkForFile($fileName) {
		return Locator::forThe()->css(".name")->descendantOf(self::rowForFile($fileName))->
				describedAs("Main link for file $fileName in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function shareActionForFile($fileName) {
		return Locator::forThe()->css(".action-share")->descendantOf(self::rowForFile($fileName))->
				describedAs("Share action for file $fileName in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function fileActionsMenuButtonForFile($fileName) {
		return Locator::forThe()->css(".action-menu")->descendantOf(self::rowForFile($fileName))->
				describedAs("File actions menu button for file $fileName in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function fileActionsMenu() {
		return Locator::forThe()->css(".fileActionsMenu")->
				describedAs("File actions menu in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function viewFileInFolderMenuItem() {
		return self::fileActionsMenuItemFor("View in folder");
	}

	/**
	 * @return Locator
	 */
	private static function fileActionsMenuItemFor($itemText) {
		return Locator::forThe()->content($itemText)->descendantOf(self::fileActionsMenu())->
				describedAs($itemText . " item in file actions menu in Files app");
	}

	/**
	 * @Given I open the :section section
	 */
	public function iOpenTheSection($section) {
		$this->actor->find(self::appNavigationSectionItemFor($section), 10)->click();
	}

	/**
	 * @Given I open the details view for :fileName
	 */
	public function iOpenTheDetailsViewFor($fileName) {
		$this->actor->find(self::mainLinkForFile($fileName), 10)->click();
	}

	/**
	 * @Given I mark :fileName as favorite
	 */
	public function iMarkAsFavorite($fileName) {
		$this->actor->find(self::favoriteActionForFile($fileName), 10)->click();
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
	 * @When I view :fileName in folder
	 */
	public function iViewInFolder($fileName) {
		$this->actor->find(self::fileActionsMenuButtonForFile($fileName), 10)->click();

		$this->actor->find(self::viewFileInFolderMenuItem(), 2)->click();
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
	 * @Then I see that the current section is :section
	 */
	public function iSeeThatTheCurrentSectionIs($section) {
		PHPUnit_Framework_Assert::assertEquals($this->actor->find(self::appNavigationCurrentSectionItem(), 10)->getText(), $section);
	}

	/**
	 * @Then I see that the details view for :section section is open
	 */
	public function iSeeThatTheDetailsViewForSectionIsOpen($section) {
		PHPUnit_Framework_Assert::assertTrue(
				$this->actor->find(self::detailsViewForSection($section), 10)->isVisible());

		$otherSections = self::sections();
		unset($otherSections[$section]);

		$this->assertDetailsViewForSectionsAreClosed($otherSections);
	}

	/**
	 * @Then I see that the details view is closed
	 */
	public function iSeeThatTheDetailsViewIsClosed() {
		PHPUnit_Framework_Assert::assertNotNull($this->actor->find(self::currentSectionMainView(), 10));

		$this->assertDetailsViewForSectionsAreClosed(self::sections());
	}

	private function assertDetailsViewForSectionsAreClosed($sections) {
		foreach ($sections as $section => $id) {
			try {
				PHPUnit_Framework_Assert::assertFalse(
						$this->actor->find(self::detailsViewForSection($section))->isVisible(),
						"Details view for section $section is open but it should be closed");
			} catch (NoSuchElementException $exception) {
			}
		}
	}

	/**
	 * @Then I see that :fileName is marked as favorite
	 */
	public function iSeeThatIsMarkedAsFavorite($fileName) {
		PHPUnit_Framework_Assert::assertNotNull($this->actor->find(self::favoritedStateIconForFile($fileName), 10));
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
