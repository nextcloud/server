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

class AppNavigationContext implements Context, ActorAwareInterface {

	use ActorAware;

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
	 * @Given I open the :section section
	 */
	public function iOpenTheSection($section) {
		$this->actor->find(self::appNavigationSectionItemFor($section), 10)->click();
	}

	/**
	 * @Then I see that the current section is :section
	 */
	public function iSeeThatTheCurrentSectionIs($section) {
		PHPUnit_Framework_Assert::assertEquals($this->actor->find(self::appNavigationCurrentSectionItem(), 10)->getText(), $section);
	}

}
