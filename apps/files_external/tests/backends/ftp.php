<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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

class FTP extends Storage {
	private $config;

	protected function setUp() {
		parent::setUp();

		$id = $this->getUniqueID();
		$this->config = include('files_external/tests/config.ftp.php');
		if ( ! is_array($this->config) or ! $this->config['run']) {
			$this->markTestSkipped('FTP backend not configured');
		}
		$this->config['root'] .= '/' . $id; //make sure we have an new empty folder to work in
		$this->instance = new \OC\Files\Storage\FTP($this->config);
		$this->instance->mkdir('/');
	}

	protected function tearDown() {
		if ($this->instance) {
			\OCP\Files::rmdirr($this->instance->constructUrl(''));
		}

		parent::tearDown();
	}

	public function testConstructUrl(){
		$config = array ( 'host' => 'localhost',
						  'user' => 'ftp',
						  'password' => 'ftp',
						  'root' => '/',
						  'secure' => false );
		$instance = new \OC\Files\Storage\FTP($config);
		$this->assertEquals('ftp://ftp:ftp@localhost/', $instance->constructUrl(''));

		$config['secure'] = true;
		$instance = new \OC\Files\Storage\FTP($config);
		$this->assertEquals('ftps://ftp:ftp@localhost/', $instance->constructUrl(''));

		$config['secure'] = 'false';
		$instance = new \OC\Files\Storage\FTP($config);
		$this->assertEquals('ftp://ftp:ftp@localhost/', $instance->constructUrl(''));

		$config['secure'] = 'true';
		$instance = new \OC\Files\Storage\FTP($config);
		$this->assertEquals('ftps://ftp:ftp@localhost/', $instance->constructUrl(''));

		$config['root'] = '';
		$instance = new \OC\Files\Storage\FTP($config);
		$this->assertEquals('ftps://ftp:ftp@localhost/somefile.txt', $instance->constructUrl('somefile.txt'));

		$config['root'] = '/abc';
		$instance = new \OC\Files\Storage\FTP($config);
		$this->assertEquals('ftps://ftp:ftp@localhost/abc/somefile.txt', $instance->constructUrl('somefile.txt'));

		$config['root'] = '/abc/';
		$instance = new \OC\Files\Storage\FTP($config);
		$this->assertEquals('ftps://ftp:ftp@localhost/abc/somefile.txt', $instance->constructUrl('somefile.txt'));
	}
}
