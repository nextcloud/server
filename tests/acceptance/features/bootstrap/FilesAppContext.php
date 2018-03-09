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
	use FileListAncestorSetter;

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
	public static function closeDetailsViewButton() {
		return Locator::forThe()->css(".icon-close")->
				descendantOf(self::currentSectionDetailsView())->
				describedAs("Close current section details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function fileNameInCurrentSectionDetailsView() {
		return Locator::forThe()->css(".fileName")->
				descendantOf(self::currentSectionDetailsView())->
				describedAs("File name in current section details view in Files app");
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
	public static function allowUploadAndEditingRadioButton() {
		// forThe()->radio("Allow upload and editing") can not be used here;
		// that would return the radio button itself, but the element that the
		// user interacts with is the label.
		return Locator::forThe()->xpath("//label[normalize-space() = 'Allow upload and editing']")->
				descendantOf(self::currentSectionDetailsView())->
				describedAs("Allow upload and editing radio button in the details view in Files app");
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
	 * @Given I close the details view
	 */
	public function iCloseTheDetailsView() {
		$this->actor->find(self::closeDetailsViewButton(), 10)->click();
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
	 * @Given I share the link for :fileName
	 */
	public function iShareTheLinkFor($fileName) {
		$this->actor->find(FileListContext::shareActionForFile(self::currentSectionMainView(), $fileName), 10)->click();

		$this->actor->find(self::shareLinkCheckbox(), 5)->click();
	}

	/**
	 * @Given I write down the shared link
	 */
	public function iWriteDownTheSharedLink() {
		// The shared link field always exists in the DOM (once the "Sharing"
		// tab is loaded), but its value is the actual shared link only when it
		// is visible.
		if (!WaitFor::elementToBeEventuallyShown(
				$this->actor,
				self::shareLinkField(),
				$timeout = 10 * $this->actor->getFindTimeoutMultiplier())) {
			PHPUnit_Framework_Assert::fail("The shared link was not shown yet after $timeout seconds");
		}

		$this->actor->getSharedNotebook()["shared link"] = $this->actor->find(self::shareLinkField())->getValue();
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
	 * @When I set the shared link as editable
	 */
	public function iSetTheSharedLinkAsEditable() {
		$this->actor->find(self::allowUploadAndEditingRadioButton(), 10)->click();
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

		$this->setFileListAncestorForActor(self::currentSectionMainView(), $this->actor);
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
	 * @Then I see that the file name shown in the details view is :fileName
	 */
	public function iSeeThatTheFileNameShownInTheDetailsViewIs($fileName) {
		PHPUnit_Framework_Assert::assertEquals(
				$this->actor->find(self::fileNameInCurrentSectionDetailsView(), 10)->getText(), $fileName);
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
		if (!WaitFor::elementToBeEventuallyNotShown(
				$this->actor,
				self::loadingIconForTabInCurrentSectionDetailsViewNamed($tabName),
				$timeout = 10 * $this->actor->getFindTimeoutMultiplier())) {
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
		if (!WaitFor::elementToBeEventuallyNotShown(
				$this->actor,
				self::passwordProtectWorkingIcon(),
				$timeout = 10 * $this->actor->getFindTimeoutMultiplier())) {
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
