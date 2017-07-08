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
	public static function fileDetailsInCurrentSectionDetailsViewWithText($fileDetailsText) {
		return Locator::forThe()->xpath("//span[normalize-space() = '$fileDetailsText']")->
				descendantOf(self::fileDetailsInCurrentSectionDetailsView())->
				describedAs("File details with text \"$fileDetailsText\" in current section details view in Files app");
	}

	/**
	 * @return Locator
	 */
	private static function fileDetailsInCurrentSectionDetailsView() {
		return Locator::forThe()->css(".file-details")->
				descendantOf(self::currentSectionDetailsView())->
				describedAs("File details in current section details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function inputFieldForTagsInCurrentSectionDetailsView() {
		return Locator::forThe()->css(".systemTagsInfoView")->
				descendantOf(self::currentSectionDetailsView())->
				describedAs("Input field for tags in current section details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function itemInInputFieldForTagsInCurrentSectionDetailsViewForTag($tag) {
		return Locator::forThe()->xpath("//span[normalize-space() = '$tag']")->
				descendantOf(self::inputFieldForTagsInCurrentSectionDetailsView())->
				describedAs("Item in input field for tags in current section details view for tag $tag in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function itemInDropdownForTag($tag) {
		return Locator::forThe()->xpath("//*[contains(concat(' ', normalize-space(@class), ' '), ' select2-result-label ')]//span[normalize-space() = '$tag']/ancestor::li")->
				descendantOf(self::select2Dropdown())->
				describedAs("Item in dropdown for tag $tag in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function checkmarkInItemInDropdownForTag($tag) {
		return Locator::forThe()->css(".checkmark")->
				descendantOf(self::itemInDropdownForTag($tag))->
				describedAs("Checkmark in item in dropdown for tag $tag in Files app");
	}

	/**
	 * @return Locator
	 */
	private static function select2Dropdown() {
		return Locator::forThe()->css("#select2-drop")->
				describedAs("Select2 dropdown in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function tabHeaderInCurrentSectionDetailsViewNamed($tabHeaderName) {
		return Locator::forThe()->xpath("//li[normalize-space() = '$tabHeaderName']")->
				descendantOf(self::tabHeadersInCurrentSectionDetailsView())->
				describedAs("Tab header named $tabHeaderName in current section details view in Files app");
	}

	/**
	 * @return Locator
	 */
	private static function tabHeadersInCurrentSectionDetailsView() {
		return Locator::forThe()->css(".tabHeaders")->
				descendantOf(self::currentSectionDetailsView())->
				describedAs("Tab headers in current section details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function tabInCurrentSectionDetailsViewNamed($tabName) {
		return Locator::forThe()->xpath("//div[@id=//*[contains(concat(' ', normalize-space(@class), ' '), ' tabHeader ') and normalize-space() = '$tabName']/@data-tabid]")->
				descendantOf(self::currentSectionDetailsView())->
				describedAs("Tab named $tabName in current section details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function loadingIconForTabInCurrentSectionDetailsViewNamed($tabName) {
		return Locator::forThe()->css(".loading")->
				descendantOf(self::tabInCurrentSectionDetailsViewNamed($tabName))->
				describedAs("Loading icon for tab named $tabName in current section details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function shareLinkCheckbox() {
		// forThe()->checkbox("Share link") can not be used here; that would
		// return the checkbox itself, but the element that the user interacts
		// with is the label.
		return Locator::forThe()->xpath("//label[normalize-space() = 'Share link']")->
				descendantOf(self::currentSectionDetailsView())->
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
		// forThe()->checkbox("Password protect") can not be used here; that
		// would return the checkbox itself, but the element that the user
		// interacts with is the label.
		return Locator::forThe()->xpath("//label[normalize-space() = 'Password protect']")->
				descendantOf(self::currentSectionDetailsView())->
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
	public static function createMenuButton() {
		return Locator::forThe()->css("#controls .button.new")->
				descendantOf(self::currentSectionMainView())->
				describedAs("Create menu button in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function createNewFolderMenuItem() {
		return self::createMenuItemFor("New folder");
	}

	/**
	 * @return Locator
	 */
	public static function createNewFolderMenuItemNameInput() {
		return Locator::forThe()->css(".filenameform input")->
				descendantOf(self::createNewFolderMenuItem())->
				describedAs("Name input in create new folder menu item in Files app");
	}

	/**
	 * @return Locator
	 */
	private static function createMenuItemFor($newType) {
		return Locator::forThe()->xpath("//div[contains(concat(' ', normalize-space(@class), ' '), ' newFileMenu ')]//span[normalize-space() = '$newType']/ancestor::li")->
				descendantOf(self::currentSectionMainView())->
				describedAs("Create $newType menu item in Files app");
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
	public static function rowForFilePreceding($fileName1, $fileName2) {
		return Locator::forThe()->xpath("//preceding-sibling::tr//span[contains(concat(' ', normalize-space(@class), ' '), ' nametext ') and normalize-space() = '$fileName1']/ancestor::tr")->
				descendantOf(self::rowForFile($fileName2))->
				describedAs("Row for file $fileName1 preceding $fileName2 in Files app");
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
	public static function notFavoritedStateIconForFile($fileName) {
		return Locator::forThe()->css(".icon-star")->descendantOf(self::favoriteActionForFile($fileName))->
				describedAs("Not favorited state icon for file $fileName in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function favoritedStateIconForFile($fileName) {
		return Locator::forThe()->css(".icon-starred")->descendantOf(self::favoriteActionForFile($fileName))->
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
	public static function detailsMenuItem() {
		return self::fileActionsMenuItemFor("Details");
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
		return Locator::forThe()->xpath("//a[normalize-space() = '$itemText']")->
				descendantOf(self::fileActionsMenu())->
				describedAs($itemText . " item in file actions menu in Files app");
	}

	/**
	 * @Given I create a new folder named :folderName
	 */
	public function iCreateANewFolderNamed($folderName) {
		$this->actor->find(self::createMenuButton(), 10)->click();

		$this->actor->find(self::createNewFolderMenuItem(), 2)->click();
		$this->actor->find(self::createNewFolderMenuItemNameInput(), 2)->setValue($folderName . "\r");
	}

	/**
	 * @Given I open the details view for :fileName
	 */
	public function iOpenTheDetailsViewFor($fileName) {
		$this->actor->find(self::fileActionsMenuButtonForFile($fileName), 10)->click();

		$this->actor->find(self::detailsMenuItem(), 2)->click();
	}

	/**
	 * @Given I open the input field for tags in the details view
	 */
	public function iOpenTheInputFieldForTagsInTheDetailsView() {
		$this->actor->find(self::fileDetailsInCurrentSectionDetailsViewWithText("Tags"), 10)->click();
	}

	/**
	 * @Given I open the :tabName tab in the details view
	 */
	public function iOpenTheTabInTheDetailsView($tabName) {
		$this->actor->find(self::tabHeaderInCurrentSectionDetailsViewNamed($tabName), 10)->click();
	}

	/**
	 * @Given I mark :fileName as favorite
	 */
	public function iMarkAsFavorite($fileName) {
		$this->iSeeThatIsNotMarkedAsFavorite($fileName);

		$this->actor->find(self::favoriteActionForFile($fileName), 10)->click();
	}

	/**
	 * @Given I unmark :fileName as favorite
	 */
	public function iUnmarkAsFavorite($fileName) {
		$this->iSeeThatIsMarkedAsFavorite($fileName);

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
	 * @When I check the tag :tag in the dropdown for tags in the details view
	 */
	public function iCheckTheTagInTheDropdownForTagsInTheDetailsView($tag) {
		$this->iSeeThatTheTagInTheDropdownForTagsInTheDetailsViewIsNotChecked($tag);

		$this->actor->find(self::itemInDropdownForTag($tag), 10)->click();
	}

	/**
	 * @When I uncheck the tag :tag in the dropdown for tags in the details view
	 */
	public function iUncheckTheTagInTheDropdownForTagsInTheDetailsView($tag) {
		$this->iSeeThatTheTagInTheDropdownForTagsInTheDetailsViewIsChecked($tag);

		$this->actor->find(self::itemInDropdownForTag($tag), 10)->click();
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
	 * @Then I see that :fileName1 precedes :fileName2 in the file list
	 */
	public function iSeeThatPrecedesInTheFileList($fileName1, $fileName2) {
		PHPUnit_Framework_Assert::assertNotNull($this->actor->find(self::rowForFilePreceding($fileName1, $fileName2), 10));
	}

	/**
	 * @Then I see that :fileName is marked as favorite
	 */
	public function iSeeThatIsMarkedAsFavorite($fileName) {
		PHPUnit_Framework_Assert::assertNotNull($this->actor->find(self::favoritedStateIconForFile($fileName), 10));
	}

	/**
	 * @Then I see that :fileName is not marked as favorite
	 */
	public function iSeeThatIsNotMarkedAsFavorite($fileName) {
		PHPUnit_Framework_Assert::assertNotNull($this->actor->find(self::notFavoritedStateIconForFile($fileName), 10));
	}

	/**
	 * @Then I see that the input field for tags in the details view is shown
	 */
	public function iSeeThatTheInputFieldForTagsInTheDetailsViewIsShown() {
		PHPUnit_Framework_Assert::assertTrue(
				$this->actor->find(self::inputFieldForTagsInCurrentSectionDetailsView(), 10)->isVisible());
	}

	/**
	 * @Then I see that the input field for tags in the details view contains the tag :tag
	 */
	public function iSeeThatTheInputFieldForTagsInTheDetailsViewContainsTheTag($tag) {
		PHPUnit_Framework_Assert::assertTrue(
				$this->actor->find(self::itemInInputFieldForTagsInCurrentSectionDetailsViewForTag($tag), 10)->isVisible());
	}

	/**
	 * @Then I see that the input field for tags in the details view does not contain the tag :tag
	 */
	public function iSeeThatTheInputFieldForTagsInTheDetailsViewDoesNotContainTheTag($tag) {
		$this->iSeeThatTheInputFieldForTagsInTheDetailsViewIsShown();

		try {
			PHPUnit_Framework_Assert::assertFalse(
					$this->actor->find(self::itemInInputFieldForTagsInCurrentSectionDetailsViewForTag($tag))->isVisible());
		} catch (NoSuchElementException $exception) {
		}
	}

	/**
	 * @Then I see that the tag :tag in the dropdown for tags in the details view is checked
	 */
	public function iSeeThatTheTagInTheDropdownForTagsInTheDetailsViewIsChecked($tag) {
		PHPUnit_Framework_Assert::assertTrue(
				$this->actor->find(self::checkmarkInItemInDropdownForTag($tag), 10)->isVisible());
	}

	/**
	 * @Then I see that the tag :tag in the dropdown for tags in the details view is not checked
	 */
	public function iSeeThatTheTagInTheDropdownForTagsInTheDetailsViewIsNotChecked($tag) {
		PHPUnit_Framework_Assert::assertTrue(
				$this->actor->find(self::itemInDropdownForTag($tag), 10)->isVisible());

		PHPUnit_Framework_Assert::assertFalse(
				$this->actor->find(self::checkmarkInItemInDropdownForTag($tag))->isVisible());
	}

	/**
	 * @When I see that the :tabName tab in the details view is eventually loaded
	 */
	public function iSeeThatTheTabInTheDetailsViewIsEventuallyLoaded($tabName) {
		if (!$this->waitForElementToBeEventuallyNotShown(self::loadingIconForTabInCurrentSectionDetailsViewNamed($tabName), $timeout = 10)) {
			PHPUnit_Framework_Assert::fail("The $tabName tab in the details view has not been loaded after $timeout seconds");
		}
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
		if (!$this->waitForElementToBeEventuallyNotShown(self::passwordProtectWorkingIcon(), $timeout = 10)) {
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

	private function waitForElementToBeEventuallyNotShown($elementLocator, $timeout = 10, $timeoutStep = 1) {
		$actor = $this->actor;

		$elementNotFoundCallback = function() use ($actor, $elementLocator) {
			try {
				return !$actor->find($elementLocator)->isVisible();
			} catch (NoSuchElementException $exception) {
				return true;
			}
		};

		return Utils::waitFor($elementNotFoundCallback, $timeout, $timeoutStep);
	}
}
