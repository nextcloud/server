<?php

/**
 *
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

use Behat\Behat\Context\Context;

class AppsManagementContext implements Context, ActorAwareInterface {
	use ActorAware;

	/**
	 * @return Locator
	 */
	public static function enableButtonForApp($app) {
		return Locator::forThe()->button("Enable")->
			descendantOf(self::rowForApp($app))->
			describedAs("Enable button in the app list for $app");
	}

	/**
	 * @return Locator
	 */
	public static function downloadAndEnableButtonForApp($app) {
		return Locator::forThe()->button("Download and enable")->
		descendantOf(self::rowForApp($app))->
		describedAs("Download & enable button in the app list for $app");
	}

	/**
	 * @return Locator
	 */
	public static function disableButtonForApp($app) {
		return Locator::forThe()->button("Disable")->
		descendantOf(self::rowForApp($app))->
		describedAs("Disable button in the app list for $app");
	}

	/**
	 * @return Locator
	 */
	public static function bundleButton($bundle) {
		return Locator::forThe()->xpath("//main[@id='app-content']//div[@class='apps-header']/h2[normalize-space() = '$bundle']/input")->
		describedAs("Button to enable / disable bundles");
	}

	/**
	 * @return Locator
	 */
	public static function rowForApp($app) {
		return Locator::forThe()->xpath("//main[@id='app-content']//div[@class='app-name'][normalize-space() = '$app']/..")->
				describedAs("Row for app $app in Apps Management");
	}

	/**
	 * @return Locator
	 */
	public static function emptyAppList() {
		return Locator::forThe()->xpath("//main[@id='app-content']//div[@id='apps-list-empty']")->
			describedAs("Empty apps list view");
	}

	/**
	 * @return Locator
	 */
	public static function appEntries() {
		return Locator::forThe()->xpath("//main[@id='app-content']//div[@class='section']")->
			describedAs("Entries in apps list");
	}

	/**
	 * @return Locator
	 */
	public static function disabledAppEntries() {
		return Locator::forThe()->button("Disable")->
		descendantOf(self::appEntries())->
		describedAs("Disable button in the app list");
	}

	/**
	 * @return Locator
	 */
	public static function enabledAppEntries() {
		return Locator::forThe()->button("Enable")->
		descendantOf(self::appEntries())->
		describedAs("Enable button in the app list");
	}

	/**
	 * @return Locator
	 */
	public static function sidebar() {
		return Locator::forThe()->id("app-sidebar")->
		describedAs("Sidebar in apps management");
	}


	/**
	 * @When I enable the :app app
	 */
	public function iEnableTheApp($app) {
		$this->actor->find(self::enableButtonForApp($app), 10)->click();
	}

	/**
	 * @When I download and enable the :app app
	 */
	public function iDownloadAndEnableTheApp($app) {
		$this->actor->find(self::downloadAndEnableButtonForApp($app), 10)->click();
	}

	/**
	 * @When I disable the :app app
	 */
	public function iDisableTheApp($app) {
		$this->actor->find(self::disableButtonForApp($app), 10)->click();
	}

	/**
	 * @Then I see that the :app app has been enabled
	 */
	public function iSeeThatTheAppHasBeenEnabled($app) {
		// TODO: Find a way to check if the enable button is removed
		$this->actor->find(self::disableButtonForApp($app), 10);
	}

	/**
	 * @Then I see that the :app app has been disabled
	 */
	public function iSeeThatTheAppHasBeenDisabled($app) {
		// TODO: Find a way to check if the disable button is removed
		$this->actor->find(self::enableButtonForApp($app), 10);
	}

	/**
	 * @Then /^I see that there are no available updates$/
	 */
	public function iSeeThatThereAreNoAvailableUpdates() {
		PHPUnit_Framework_Assert::assertTrue(
			$this->actor->find(self::emptyAppList(), 10)->isVisible()
		);
	}

	/**
	 * @Then /^I see that there some apps listed from the app store$/
	 */
	public function iSeeThatThereSomeAppsListedFromTheAppStore() {
		WaitFor::elementToBeEventuallyShown($this->actor, self::appEntries(), 10);
	}

	/**
	 * @When /^I click on the "([^"]*)" app$/
	 */
	public function iClickOnTheApp($app) {
		$this->actor->find(self::rowForApp($app), 10)->click();
	}

	/**
	 * @Given /^I see that there are only disabled apps$/
	 */
	public function iSeeThatThereAreOnlyDisabledApps() {
		$buttons = $this->actor->getSession()->getDriver()->find("//input[@value = 'Disable']");
		PHPUnit\Framework\Assert::assertEmpty($buttons, 'Found disabled apps');
	}

	/**
	 * @Given /^I see that there are only enabled apps$/
	 */
	public function iSeeThatThereAreOnlyEnabledApps() {
		$buttons = $this->actor->getSession()->getDriver()->find("//input[@value = 'Enable']");
		PHPUnit\Framework\Assert::assertEmpty($buttons, 'Found disabled apps');
	}

	/**
	 * @Given /^I see the app bundles$/
	 */
	public function iSeeTheAppBundles() {
		$this->actor->find(self::rowForApp('Auditing / Logging'), 2);
		$this->actor->find(self::rowForApp('LDAP user and group backend'), 2);
	}

	/**
	 * @When /^I enable all apps from the "([^"]*)"$/
	 */
	public function iEnableAllAppsFromThe($bundle) {
		$this->actor->find(self::bundleButton($bundle), 2)->click();
	}

	/**
	 * @Given /^I see that the "([^"]*)" is disabled$/
	 */
	public function iSeeThatTheIsDisabled($bundle) {
		PHPUnit\Framework\Assert::assertEquals('Enable all', $this->actor->find(self::bundleButton($bundle))->getValue());
	}

	/**
	 * @Given /^I see that the app details are shown$/
	 */
	public function iSeeThatTheAppDetailsAreShown() {
		// The sidebar always exists in the DOM, so it has to be explicitly
		// waited for it to be visible instead of relying on the implicit wait
		// made to find the element.
		if (!WaitFor::elementToBeEventuallyShown(
			$this->actor,
			self::sidebar(),
			$timeout = 10 * $this->actor->getFindTimeoutMultiplier())) {
			PHPUnit_Framework_Assert::fail("The sidebar was not shown yet after $timeout seconds");
		}
	}
}
