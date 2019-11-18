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

class NotificationContext implements Context, ActorAwareInterface {

	use ActorAware;

	/**
	 * @return Locator
	 */
	public static function notificationMessage($message) {
		return Locator::forThe()->xpath("//*[contains(concat(' ', normalize-space(@class), ' '), ' toastify ') and normalize-space(text()) = '$message']")->
				descendantOf(self::notificationContainer())->
				describedAs("$message notification");
	}

	/**
	 * @return Locator
	 */
	private static function notificationContainer() {
		return Locator::forThe()->id("content")->
				describedAs("Notification container");
	}

	/**
	 * @Then I see that the :message notification is shown
	 */
	public function iSeeThatTheNotificationIsShown($message) {
		PHPUnit_Framework_Assert::assertTrue($this->actor->find(
				self::notificationMessage($message), 10)->isVisible());
	}

}
