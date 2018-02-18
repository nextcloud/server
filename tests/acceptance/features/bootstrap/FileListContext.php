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

class FileListContext implements Context, ActorAwareInterface {

	use ActorAware;

	/**
	 * @return Locator
	 */
	public static function rowForFile($fileName) {
		return Locator::forThe()->xpath("//*[@id = 'fileList']//span[contains(concat(' ', normalize-space(@class), ' '), ' nametext ') and normalize-space() = '$fileName']/ancestor::tr")->
				descendantOf(FilesAppContext::currentSectionMainView())->
				describedAs("Row for file $fileName in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function favoriteActionForFile($fileName) {
		return Locator::forThe()->css(".action-favorite")->
				descendantOf(self::rowForFile($fileName))->
				describedAs("Favorite action for file $fileName in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function favoritedStateIconForFile($fileName) {
		return Locator::forThe()->css(".icon-starred")->
				descendantOf(self::favoriteActionForFile($fileName))->
				describedAs("Favorited state icon for file $fileName in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function mainLinkForFile($fileName) {
		return Locator::forThe()->css(".name")->
				descendantOf(self::rowForFile($fileName))->
				describedAs("Main link for file $fileName in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function renameInputForFile($fileName) {
		return Locator::forThe()->css("input.filename")->
				descendantOf(self::rowForFile($fileName))->
				describedAs("Rename input for file $fileName in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function shareActionForFile($fileName) {
		return Locator::forThe()->css(".action-share")->
				descendantOf(self::rowForFile($fileName))->
				describedAs("Share action for file $fileName in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function fileActionsMenuButtonForFile($fileName) {
		return Locator::forThe()->css(".action-menu")->
				descendantOf(self::rowForFile($fileName))->
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
	private static function fileActionsMenuItemFor($itemText) {
		return Locator::forThe()->xpath("//a[normalize-space() = '$itemText']")->
				descendantOf(self::fileActionsMenu())->
				describedAs($itemText . " item in file actions menu in Files app");
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
	public static function renameMenuItem() {
		return self::fileActionsMenuItemFor("Rename");
	}

	/**
	 * @return Locator
	 */
	public static function viewFileInFolderMenuItem() {
		return self::fileActionsMenuItemFor("View in folder");
	}

	/**
	 * @Given I open the details view for :fileName
	 */
	public function iOpenTheDetailsViewFor($fileName) {
		$this->actor->find(self::fileActionsMenuButtonForFile($fileName), 10)->click();

		$this->actor->find(self::detailsMenuItem(), 2)->click();
	}

	/**
	 * @Given I rename :fileName1 to :fileName2
	 */
	public function iRenameTo($fileName1, $fileName2) {
		$this->actor->find(self::fileActionsMenuButtonForFile($fileName1), 10)->click();

		$this->actor->find(self::renameMenuItem(), 2)->click();

		$this->actor->find(self::renameInputForFile($fileName1), 10)->setValue($fileName2 . "\r");
	}

	/**
	 * @Given I mark :fileName as favorite
	 */
	public function iMarkAsFavorite($fileName) {
		$this->actor->find(self::favoriteActionForFile($fileName), 10)->click();
	}

	/**
	 * @When I view :fileName in folder
	 */
	public function iViewInFolder($fileName) {
		$this->actor->find(self::fileActionsMenuButtonForFile($fileName), 10)->click();

		$this->actor->find(self::viewFileInFolderMenuItem(), 2)->click();
	}

	/**
	 * @Then I see that the file list contains a file named :fileName
	 */
	public function iSeeThatTheFileListContainsAFileNamed($fileName) {
		PHPUnit_Framework_Assert::assertNotNull($this->actor->find(self::rowForFile($fileName), 10));
	}

	/**
	 * @Then I see that :fileName is marked as favorite
	 */
	public function iSeeThatIsMarkedAsFavorite($fileName) {
		PHPUnit_Framework_Assert::assertNotNull($this->actor->find(self::favoritedStateIconForFile($fileName), 10));
	}

}
