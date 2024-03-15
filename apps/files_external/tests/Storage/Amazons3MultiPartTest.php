<?php
/**
 * @copyright Copyright (c) 2023 Robin Appelman <robin@icewind.nl>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @author Robin Appelman <robin@icewind.nl>
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
namespace OCA\files_external\tests\Storage;

use OCA\Files_External\Lib\Storage\AmazonS3;

/**
 * Class Amazons3Test
 *
 * @group DB
 *
 * @package OCA\Files_External\Tests\Storage
 */
class Amazons3MultiPartTest extends \Test\Files\Storage\Storage {
	private $config;
	/** @var AmazonS3 */
	protected $instance;

	protected function setUp(): void {
		parent::setUp();

		$this->config = include('files_external/tests/config.amazons3.php');
		if (! is_array($this->config) or ! $this->config['run']) {
			$this->markTestSkipped('AmazonS3 backend not configured');
		}
		$this->instance = new AmazonS3($this->config + [
			'putSizeLimit' => 1,
			'copySizeLimit' => 1,
		]);
	}

	protected function tearDown(): void {
		if ($this->instance) {
			$this->instance->rmdir('');
		}

		parent::tearDown();
	}

	public function testStat() {
		$this->markTestSkipped('S3 doesn\'t update the parents folder mtime');
	}

	public function testHashInFileName() {
		$this->markTestSkipped('Localstack has a bug with hashes in filename');
	}
}
