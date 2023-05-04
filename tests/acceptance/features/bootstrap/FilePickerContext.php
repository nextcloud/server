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

class FilePickerContext implements Context, ActorAwareInterface {
	use ActorAware;

	/**
	 * @return Locator
	 */
	public static function dialog() {
		return Locator::forThe()->css(".oc-dialog")->
				describedAs("File picker dialog");
	}

	/**
	 * @return Locator
	 */
	public static function fileListContainer() {
		return Locator::forThe()->css("#oc-dialog-filepicker-content")->
				descendantOf(self::dialog())->
				describedAs("File list container in the file picker dialog");
	}

	/**
	 * @return Locator
	 */
	public static function rowForFile($fileName) {
		// File names in the file picker are split in two span elements, so
		// their texts need to be concatenated to get the full file name.
		return Locator::forThe()->xpath("//*[@id = 'picker-filestable']//*[contains(concat(' ', normalize-space(@class), ' '), ' filename-parts ') and concat(span[1], span[2]) = '$fileName']/ancestor::tr")->
				descendantOf(self::fileListContainer())->
				describedAs("Row for file $fileName in the file picker dialog");
	}

	/**
	 * @return Locator
	 */
	public static function buttonRow() {
		return Locator::forThe()->css(".oc-dialog-buttonrow")->
				descendantOf(self::dialog())->
				describedAs("Button row in the file picker dialog");
	}

	/**
	 * @return Locator
	 */
	private static function buttonFor($buttonText) {
		// "Copy" and "Move" buttons text is set to "Copy to XXX" and "Move to
		// XXX" when a folder is selected.
		return Locator::forThe()->xpath("//button[starts-with(normalize-space(), '$buttonText')]")->
				descendantOf(self::buttonRow())->
				describedAs($buttonText . " button in the file picker dialog");
	}

	/**
	 * @return Locator
	 */
	public static function copyButton() {
		return self::buttonFor("Copy");
	}

	/**
	 * @return Locator
	 */
	public static function moveButton() {
		return self::buttonFor("Move");
	}

	/**
	 * @return Locator
	 */
	public static function chooseButton() {
		return self::buttonFor("Choose");
	}

	/**
	 * @When I select :fileName in the file picker
	 */
	public function iSelectInTheFilePicker($fileName) {
		$this->actor->find(self::rowForFile($fileName), 10)->click();
	}

	/**
	 * @When I copy to the last selected folder in the file picker
	 */
	public function iCopyToTheLastSelectedFolderInTheFilePicker() {
		$this->actor->find(self::copyButton(), 10)->click();
	}

	/**
	 * @When I move to the last selected folder in the file picker
	 */
	public function iMoveToTheLastSelectedFolderInTheFilePicker() {
		$this->actor->find(self::moveButton(), 10)->click();
	}

	/**
	 * @When I choose the last selected file in the file picker
	 */
	public function iChooseTheLastSelectedFileInTheFilePicker() {
		$this->actor->find(self::chooseButton(), 10)->click();
	}
}
