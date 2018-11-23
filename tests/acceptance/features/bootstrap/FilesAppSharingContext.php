<?php

/**
 *
 * @copyright Copyright (c) 2018, Daniel Calviño Sánchez (danxuliu@gmail.com)
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

class FilesAppSharingContext implements Context, ActorAwareInterface {

	use ActorAware;

	/**
	 * @return Locator
	 */
	public static function sharedByLabel() {
		return Locator::forThe()->css(".reshare")->
				descendantOf(FilesAppContext::detailsView())->
				describedAs("Shared by label in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function shareWithInput() {
		return Locator::forThe()->css(".shareWithField")->
				descendantOf(FilesAppContext::detailsView())->
				describedAs("Share with input in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function shareeList() {
		return Locator::forThe()->css(".shareeListView")->
				descendantOf(FilesAppContext::detailsView())->
				describedAs("Sharee list in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function sharedWithRow($sharedWithName) {
		// "username" class is used for any type of share, not only for shares
		// with users.
		return Locator::forThe()->xpath("//span[contains(concat(' ', normalize-space(@class), ' '), ' username ') and normalize-space() = '$sharedWithName']/ancestor::li")->
				descendantOf(self::shareeList())->
				describedAs("Shared with $sharedWithName row in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function shareWithMenuButton($sharedWithName) {
		return Locator::forThe()->css(".share-menu > .icon")->
				descendantOf(self::sharedWithRow($sharedWithName))->
				describedAs("Share with $sharedWithName menu button in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function shareWithMenu($sharedWithName) {
		return Locator::forThe()->css(".share-menu > .menu")->
				descendantOf(self::sharedWithRow($sharedWithName))->
				describedAs("Share with $sharedWithName menu in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function canReshareCheckbox($sharedWithName) {
		// forThe()->checkbox("Can reshare") can not be used here; that would
		// return the checkbox itself, but the element that the user interacts
		// with is the label.
		return Locator::forThe()->xpath("//label[normalize-space() = 'Can reshare']")->
				descendantOf(self::shareWithMenu($sharedWithName))->
				describedAs("Can reshare checkbox in the share with $sharedWithName menu in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function canReshareCheckboxInput($sharedWithName) {
		return Locator::forThe()->checkbox("Can reshare")->
				descendantOf(self::shareWithMenu($sharedWithName))->
				describedAs("Can reshare checkbox input in the share with $sharedWithName menu in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function shareLinkRow() {
		return Locator::forThe()->id("shareLink")->
				descendantOf(FilesAppContext::detailsView())->
				describedAs("Share link row in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function shareLinkCheckbox() {
		// forThe()->checkbox("Enable") can not be used here; that would return
		// the checkbox itself, but the element that the user interacts with is
		// the label.
		return Locator::forThe()->xpath("//label[normalize-space() = 'Enable']")->
				descendantOf(self::shareLinkRow())->
				describedAs("Share link checkbox in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function shareLinkMenuButton() {
		return Locator::forThe()->css(".share-menu > .icon")->
				descendantOf(self::shareLinkRow())->
				describedAs("Share link menu button in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function shareLinkMenu() {
		return Locator::forThe()->css(".share-menu > .menu")->
				descendantOf(self::shareLinkRow())->
				describedAs("Share link menu in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function copyUrlMenuItem() {
		return Locator::forThe()->xpath("//a[normalize-space() = 'Copy URL']")->
				descendantOf(self::shareLinkMenu())->
				describedAs("Copy URL menu item in the share link menu in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function allowUploadAndEditingRadioButton() {
		// forThe()->radio("Allow upload and editing") can not be used here;
		// that would return the radio button itself, but the element that the
		// user interacts with is the label.
		return Locator::forThe()->xpath("//label[normalize-space() = 'Allow upload and editing']")->
				descendantOf(self::shareLinkMenu())->
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
				descendantOf(self::shareLinkMenu())->
				describedAs("Password protect checkbox in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function passwordProtectField() {
		return Locator::forThe()->css(".linkPassText")->descendantOf(self::shareLinkMenu())->
				describedAs("Password protect field in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function passwordProtectWorkingIcon() {
		return Locator::forThe()->css(".linkPassMenu .icon-loading-small")->descendantOf(self::shareLinkMenu())->
				describedAs("Password protect working icon in the details view in Files app");
	}

	/**
	 * @Given I share the link for :fileName
	 */
	public function iShareTheLinkFor($fileName) {
		$this->actor->find(FileListContext::shareActionForFile(FilesAppContext::currentSectionMainView(), $fileName), 10)->click();

		$this->actor->find(self::shareLinkCheckbox(), 5)->click();
	}

	/**
	 * @Given I share :fileName with :shareWithName
	 */
	public function iShareWith($fileName, $shareWithName) {
		$this->actor->find(FileListContext::shareActionForFile(FilesAppContext::currentSectionMainView(), $fileName), 10)->click();

		$this->actor->find(self::shareWithInput(), 5)->setValue($shareWithName . "\r");
	}

	/**
	 * @Given I write down the shared link
	 */
	public function iWriteDownTheSharedLink() {
		$this->showShareLinkMenuIfNeeded();

		$this->actor->find(self::copyUrlMenuItem(), 2)->click();

		// Clicking on the menu item copies the link to the clipboard, but it is
		// not possible to access that value from the acceptance tests. Due to
		// this the value of the attribute that holds the URL is used instead.
		$this->actor->getSharedNotebook()["shared link"] = $this->actor->find(self::copyUrlMenuItem(), 2)->getWrappedElement()->getAttribute("data-clipboard-text");
	}

	/**
	 * @When I set the shared link as editable
	 */
	public function iSetTheSharedLinkAsEditable() {
		$this->showShareLinkMenuIfNeeded();

		$this->actor->find(self::allowUploadAndEditingRadioButton(), 2)->click();
	}

	/**
	 * @When I protect the shared link with the password :password
	 */
	public function iProtectTheSharedLinkWithThePassword($password) {
		$this->showShareLinkMenuIfNeeded();

		$this->actor->find(self::passwordProtectCheckbox(), 2)->click();

		$this->actor->find(self::passwordProtectField(), 2)->setValue($password . "\r");
	}

	/**
	 * @When I set the share with :shareWithName as not reshareable
	 */
	public function iSetTheShareWithAsNotReshareable($shareWithName) {
		$this->showShareWithMenuIfNeeded($shareWithName);

		$this->iSeeThatCanReshareTheShare($shareWithName);

		$this->actor->find(self::canReshareCheckbox($shareWithName), 2)->click();
	}

	/**
	 * @Then I see that the file is shared with me by :sharedByName
	 */
	public function iSeeThatTheFileIsSharedWithMeBy($sharedByName) {
		PHPUnit_Framework_Assert::assertEquals(
				$this->actor->find(self::sharedByLabel(), 10)->getText(), "Shared with you by $sharedByName");
	}

	/**
	 * @Then I see that the file is shared with :sharedWithName
	 */
	public function iSeeThatTheFileIsSharedWith($sharedWithName) {
		PHPUnit_Framework_Assert::assertTrue(
				$this->actor->find(self::sharedWithRow($sharedWithName), 10)->isVisible());
	}

	/**
	 * @Then I see that resharing the file is not allowed
	 */
	public function iSeeThatResharingTheFileIsNotAllowed() {
		PHPUnit_Framework_Assert::assertEquals(
				$this->actor->find(self::shareWithInput(), 10)->getWrappedElement()->getAttribute("disabled"), "disabled");
		PHPUnit_Framework_Assert::assertEquals(
				$this->actor->find(self::shareWithInput(), 10)->getWrappedElement()->getAttribute("placeholder"), "Resharing is not allowed");
	}

	/**
	 * @Then I see that :sharedWithName can reshare the share
	 */
	public function iSeeThatCanReshareTheShare($sharedWithName) {
		$this->showShareWithMenuIfNeeded($sharedWithName);

		PHPUnit_Framework_Assert::assertTrue(
				$this->actor->find(self::canReshareCheckboxInput($sharedWithName), 10)->isChecked());
	}

	/**
	 * @Then I see that :sharedWithName can not reshare the share
	 */
	public function iSeeThatCanNotReshareTheShare($sharedWithName) {
		$this->showShareWithMenuIfNeeded($sharedWithName);

		PHPUnit_Framework_Assert::assertFalse(
				$this->actor->find(self::canReshareCheckboxInput($sharedWithName), 10)->isChecked());
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

	private function showShareLinkMenuIfNeeded() {
		// In some cases the share menu is hidden after clicking on an action of
		// the menu. Therefore, if the menu is visible, wait a little just in
		// case it is in the process of being hidden due to a previous action,
		// in which case it is shown again.
		if (WaitFor::elementToBeEventuallyNotShown(
				$this->actor,
				self::shareLinkMenu(),
				$timeout = 2 * $this->actor->getFindTimeoutMultiplier())) {
			$this->actor->find(self::shareLinkMenuButton(), 10)->click();
		}
	}

	private function showShareWithMenuIfNeeded($shareWithName) {
		// In some cases the share menu is hidden after clicking on an action of
		// the menu. Therefore, if the menu is visible, wait a little just in
		// case it is in the process of being hidden due to a previous action,
		// in which case it is shown again.
		if (WaitFor::elementToBeEventuallyNotShown(
				$this->actor,
				self::shareWithMenu($shareWithName),
				$timeout = 2 * $this->actor->getFindTimeoutMultiplier())) {
			$this->actor->find(self::shareWithMenuButton($shareWithName), 10)->click();
		}
	}
}
