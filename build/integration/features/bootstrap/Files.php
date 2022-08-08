<?php
/**
 * @copyright Copyright (c) 2022 Daniel Calviño Sánchez <danxuliu@gmail.com>
 *
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use PHPUnit\Framework\Assert;

require __DIR__ . '/../../vendor/autoload.php';

trait Files {

	// BasicStructure trait is expected to be used in the class that uses this
	// trait.
	// CommandLineContext is expected to be loaded in the Behat suite where this
	// trait is used.

	/**
	 * @AfterScenario
	 */
	public function disableMemcacheLocal(AfterScenarioScope $scope) {
		$environment = $scope->getEnvironment();
		$commandLineContext = $environment->getContext('CommandLineContext');

		// If APCu was set APC needs to be enabled for the CLI when running OCC;
		// otherwise OC\Memcache\APCu is not available and OCC command fails,
		// even if it is just trying to disable the memcache.
		$commandLineContext->runOcc(['config:system:delete', 'memcache.local'], ['--define', 'apc.enable_cli=1']);
	}

	/**
	 * @When logged in user gets storage stats of folder :folder
	 *
	 * @param string $folder
	 */
	public function loggedInUserGetsStorageStatsOfFolder(string $folder) {
		$this->loggedInUserGetsStorageStatsOfFolderWith($folder, '200');
	}

	/**
	 * @When logged in user gets storage stats of folder :folder with :statusCode
	 *
	 * @param string $folder
	 */
	public function loggedInUserGetsStorageStatsOfFolderWith(string $folder, string $statusCode) {
		$this->sendingAToWithRequesttoken('GET', '/index.php/apps/files/ajax/getstoragestats?dir=' . $folder);
		$this->theHTTPStatusCodeShouldBe($statusCode);
	}

	/**
	 * @Then the storage stats match with
	 *
	 * @param Behat\Gherkin\Node\TableNode $body
	 */
	public function theStorageStatsMatchWith(Behat\Gherkin\Node\TableNode $body) {
		$storageStats = json_decode($this->response->getBody()->getContents(), $asAssociativeArray = true);
		$storageStats = $storageStats['data'];

		foreach ($body->getRowsHash() as $expectedField => $expectedValue) {
			if (!array_key_exists($expectedField, $storageStats)) {
				Assert::fail("$expectedField was not found in response");
			}

			Assert::assertEquals($expectedValue, $storageStats[$expectedField], "Field '$expectedField' does not match ({$storageStats[$expectedField]})");
		}
	}
}
