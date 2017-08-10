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

class ThemingAppContext implements Context, ActorAwareInterface {

	use ActorAware;

	/**
	 * @return Locator
	 */
	public static function inputFieldFor($parameterName) {
		return Locator::forThe()->css("input")->
				descendantOf(self::parameterDivFor($parameterName))->
				describedAs("Input field for $parameterName parameter in Theming app");
	}

	/**
	 * @return Locator
	 */
	public static function resetButtonFor($parameterName) {
		return Locator::forThe()->css(".theme-undo")->
				descendantOf(self::parameterDivFor($parameterName))->
				describedAs("Reset button for $parameterName parameter in Theming app");
	}

	/**
	 * @return Locator
	 */
	private static function parameterDivFor($parameterName) {
		return Locator::forThe()->xpath("//*[@id='theming']//label//*[normalize-space() = '$parameterName']/ancestor::div[1]")->
				describedAs("Div for $parameterName parameter in Theming app");
	}

	/**
	 * @return Locator
	 */
	public static function statusMessage() {
		return Locator::forThe()->id("theming_settings_msg")->
				describedAs("Status message in Theming app");
	}

	/**
	 * @When I set the :parameterName parameter in the Theming app to :parameterValue
	 */
	public function iSetTheParameterInTheThemingAppTo($parameterName, $parameterValue) {
		$this->actor->find(self::inputFieldFor($parameterName), 10)->setValue($parameterValue . "\r");
	}

	/**
	 * @When I reset the :parameterName parameter in the Theming app to its default value
	 */
	public function iSetTheParameterInTheThemingAppToItsDefaultValue($parameterName) {
		// The reset button is not shown when the cursor is outside the input
		// field, so ensure that the cursor is on the input field by clicking on
		// it.
		$this->actor->find(self::inputFieldFor($parameterName), 10)->click();

		$this->actor->find(self::resetButtonFor($parameterName), 10)->click();
	}

	/**
	 * @Then I see that the color selector in the Theming app has loaded
	 */
	public function iSeeThatTheColorSelectorInTheThemingAppHasLoaded() {
		// When the color selector is loaded it removes the leading '#' from the
		// value property of the input field object it is linked to, and changes
		// the background color of the input field to that value. The only way
		// to know that the color selector has loaded is to look for any of
		// those changes.

		PHPUnit_Framework_Assert::assertTrue($this->actor->find(self::inputFieldFor("Color"), 10)->isVisible());

		$actor = $this->actor;

		$colorSelectorLoadedCallback = function() use($actor) {
			$colorSelectorValue = $actor->getSession()->evaluateScript("return $('#theming-color')[0].value;");

			if ($colorSelectorValue[0] === '#') {
				return false;
			}

			return true;
		};

		if (!Utils::waitFor($colorSelectorLoadedCallback, $timeout = 10 * $this->actor->getFindTimeoutMultiplier(), $timeoutStep = 1)) {
			PHPUnit_Framework_Assert::fail("The color selector in Theming app has not been loaded after $timeout seconds");
		}
	}

	/**
	 * @Then I see that the header color is :color
	 */
	public function iSeeThatTheHeaderColorIs($color) {
		$headerColor = $this->actor->getSession()->evaluateScript("return $('#header').css('background-color');");

		if ($headerColor[0] === '#') {
			$headerColor = substr($headerColor, 1);
		} else if (preg_match("/rgb\(\s*(\d+),\s*(\d+),\s*(\d+)\)/", $headerColor, $matches)) {
			// Convert from hex string to RGB array
			$color = sscanf($color, "%02X%02X%02X");

			// Convert from "rgb(R, G, B)" string to RGB array
			$headerColor = array_splice($matches, 1);
		} else {
			PHPUnit_Framework_Assert::fail("The acceptance test does not know how to handle the color string returned by the browser: $headerColor");
		}

		PHPUnit_Framework_Assert::assertEquals($color, $headerColor);
	}

	/**
	 * @Then I see that the parameters in the Theming app are eventually saved
	 */
	public function iSeeThatTheParametersInTheThemingAppAreEventuallySaved() {
		PHPUnit_Framework_Assert::assertTrue($this->actor->find(self::statusMessage(), 10)->isVisible());

		$actor = $this->actor;

		$savedStatusMessageShownCallback = function() use($actor) {
			if ($actor->find(self::statusMessage())->getText() !== "Saved") {
				return false;
			}

			return true;
		};

		if (!Utils::waitFor($savedStatusMessageShownCallback, $timeout = 10 * $this->actor->getFindTimeoutMultiplier(), $timeoutStep = 1)) {
			PHPUnit_Framework_Assert::fail("The 'Saved' status messages in Theming app has not been shown after $timeout seconds");
		}
	}

}
