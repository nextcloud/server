<?php
/**
 * @author Christian Berendt <berendt@b1-systems.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
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

namespace Test\Files\Storage;

class Swift extends Storage {

	private $config;

	protected function setUp() {
		parent::setUp();

		$this->config = include('files_external/tests/config.php');
		if (!is_array($this->config) or !isset($this->config['swift'])
                    or !$this->config['swift']['run']) {
			$this->markTestSkipped('OpenStack Object Storage backend not configured');
		}
		$this->instance = new \OC\Files\Storage\Swift($this->config['swift']);
	}

	protected function tearDown() {
		if ($this->instance) {
			$connection = $this->instance->getConnection();
			$container = $connection->getContainer($this->config['swift']['bucket']);

			$objects = $container->objectList();
			while($object = $objects->next()) {
				$object->setName(str_replace('#','%23',$object->getName()));
				$object->delete();
			}

			$container->delete();
		}

		parent::tearDown();
	}
}
