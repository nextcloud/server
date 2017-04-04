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

class SettingsMenuContext implements Context, ActorAwareInterface {

	use ActorAware;

	/**
	 * @return Locator
	 */
	public static function settingsMenuButton() {
		return Locator::forThe()->xpath("//*[@id = 'header']//*[@id = 'settings']")->
				describedAs("Settings menu button");
	}

	/**
	 * @return Locator
	 */
	public static function usersMenuItem() {
		return self::menuItemFor("Users");
	}

	/**
	 * @return Locator
	 */
	public static function logOutMenuItem() {
		return self::menuItemFor("Log out");
	}

	/**
	 * @return Locator
	 */
	private static function menuItemFor($itemText) {
		return Locator::forThe()->content($itemText)->descendantOf(self::settingsMenuButton())->
				describedAs($itemText . " item in Settings menu");
	}

	/**
	 * @When I open the User settings
	 */
	public function iOpenTheUserSettings() {
		$this->actor->find(self::settingsMenuButton(), 10)->click();
		$this->actor->find(self::usersMenuItem(), 2)->click();
	}

	/**
	 * @When I log out
	 */
	public function iLogOut() {
		$this->actor->find(self::settingsMenuButton(), 10)->click();
		$this->actor->find(self::logOutMenuItem(), 2)->click();
	}

}
