<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test\App\CodeChecker;

use OC\App\CodeChecker\InfoChecker;
use OC\App\InfoParser;
use Test\TestCase;

class InfoCheckerTest extends TestCase {
	/** @var InfoChecker */
	protected $infoChecker;

	public static function setUpBeforeClass() {
		\OC::$APPSROOTS[] = [
			'path' => \OC::$SERVERROOT . '/tests/apps',
			'url' => '/apps-test',
			'writable' => false,
		];
	}

	public static function tearDownAfterClass() {
		// remove last element
		array_pop(\OC::$APPSROOTS);
	}

	protected function setUp() {
		parent::setUp();
		$infoParser = new InfoParser(\OC::$server->getURLGenerator());

		$this->infoChecker = new InfoChecker($infoParser);
	}

	public function appInfoData() {
		return [
			['testapp-infoxml', []],
			['testapp-version', []],
			['testapp-infoxml-version', []],
			['testapp-infoxml-version-different', [['type' => 'differentVersions', 'message' => 'appinfo/version: 1.2.4 - appinfo/info.xml: 1.2.3']]],
			['testapp-version-missing', []],
			['testapp-name-missing', [['type' => 'mandatoryFieldMissing', 'field' => 'name']]],
		];
	}

	/**
	 * @dataProvider appInfoData
	 *
	 * @param $appId
	 * @param $expectedErrors
	 */
	public function testApps($appId, $expectedErrors) {
		$errors = $this->infoChecker->analyse($appId);

		$this->assertEquals($expectedErrors, $errors);
	}
}
