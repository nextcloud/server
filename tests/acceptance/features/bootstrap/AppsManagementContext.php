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
use PHPUnit\Framework\Assert;

class AppsManagementContext implements Context, ActorAwareInterface {
	use ActorAware;

	/**
	 * @return Locator
	 */
	public static function appsList() {
		return Locator::forThe()->xpath("//main[@id='app-content' or contains(@class, 'app-content')]//div[@id='apps-list']")->
				describedAs("Apps list in Apps Management");
	}

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
	public static function enableButtonForAnyApp() {
		return Locator::forThe()->button("Enable")->
				descendantOf(self::appsList())->
				describedAs("Enable button in the app list for any app");
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
	public static function disableButtonForAnyApp() {
		return Locator::forThe()->button("Disable")->
				descendantOf(self::appsList())->
				describedAs("Disable button in the app list for any app");
	}

	/**
	 * @return Locator
	 */
	public static function enableAllBundleButton($bundle) {
		return Locator::forThe()->xpath("//div[@class='apps-header']/h2[normalize-space() = '$bundle']/input[@value='Enable all']")->
				descendantOf(self::appsList())->
				describedAs("Button to enable bundles");
	}

	/**
	 * @return Locator
	 */
	public static function rowForApp($app) {
		return Locator::forThe()->xpath("//div[@class='app-name'][normalize-space() = '$app']/..")->
				descendantOf(self::appsList())->
				describedAs("Row for app $app in Apps Management");
	}

	/**
	 * @return Locator
	 */
	public static function emptyAppList() {
		return Locator::forThe()->xpath("//div[@id='apps-list-empty']")->
				descendantOf(self::appsList())->
				describedAs("Empty apps list view");
	}

	/**
	 * @return Locator
	 */
	public static function appEntries() {
		return Locator::forThe()->xpath("//div[@class='section']")->
				descendantOf(self::appsList())->
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
		return Locator::forThe()->xpath("//*[@id=\"app-sidebar\" or contains(@class, 'app-sidebar')]")->
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
		Assert::assertTrue(
			$this->actor->find(self::disableButtonForApp($app), 10)->isVisible()
		);
	}

	/**
	 * @Then I see that the :app app has been disabled
	 */
	public function iSeeThatTheAppHasBeenDisabled($app) {
		// TODO: Find a way to check if the disable button is removed
		Assert::assertTrue(
			$this->actor->find(self::enableButtonForApp($app), 10)->isVisible()
		);
	}

	/**
	 * @Then /^I see that there are no available updates$/
	 */
	public function iSeeThatThereAreNoAvailableUpdates() {
		Assert::assertTrue(
			$this->actor->find(self::emptyAppList(), 10)->isVisible()
		);
	}

	/**
	 * @Then /^I see that there some apps listed from the app store$/
	 */
	public function iSeeThatThereSomeAppsListedFromTheAppStore() {
		if (!WaitFor::elementToBeEventuallyShown(
			$this->actor,
			self::appEntries(),
			$timeout = 10 * $this->actor->getFindTimeoutMultiplier())) {
			Assert::fail("The apps from the app store were not shown yet after $timeout seconds");
		}
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
		try {
			$this->actor->find(self::disableButtonForAnyApp(), 2);

			Assert::fail("Found enabled apps");
		} catch (NoSuchElementException $exception) {
		}
	}

	/**
	 * @Given /^I see that there are only enabled apps$/
	 */
	public function iSeeThatThereAreOnlyEnabledApps() {
		try {
			$this->actor->find(self::enableButtonForAnyApp(), 2);

			Assert::fail("Found disabled apps");
		} catch (NoSuchElementException $exception) {
		}
	}

	/**
	 * @Given /^I see the app bundles$/
	 */
	public function iSeeTheAppBundles() {
		Assert::assertTrue(
			$this->actor->find(self::rowForApp('Auditing / Logging'), 10)->isVisible()
		);
		Assert::assertTrue(
			$this->actor->find(self::rowForApp('LDAP user and group backend'), 2)->isVisible()
		);
	}

	/**
	 * @When /^I enable all apps from the "([^"]*)"$/
	 */
	public function iEnableAllAppsFromThe($bundle) {
		$this->actor->find(self::enableAllBundleButton($bundle), 2)->click();
	}

	/**
	 * @Given /^I see that the "([^"]*)" is disabled$/
	 */
	public function iSeeThatTheIsDisabled($bundle) {
		Assert::assertTrue(
			$this->actor->find(self::enableAllBundleButton($bundle), 2)->isVisible()
		);
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
			Assert::fail("The sidebar was not shown yet after $timeout seconds");
		}
	}
}
