<?php
/**
 * ownCloud
 *
 * @author Bjoern Schiessle
 * @copyright 2014 Bjoern Schiessle <schiessle@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

use OCA\Files\Share;
use OCA\Files_sharing\Tests\TestCase;

/**
 * Class Test_Files_Sharing
 */
class Test_Files_Sharing_Backend extends TestCase {

	const TEST_FOLDER_NAME = '/folder_share_api_test';

	public $folder;
	public $subfolder;
	public $subsubfolder;

	protected function setUp() {
		parent::setUp();

		$this->folder = self::TEST_FOLDER_NAME;
		$this->subfolder  = '/subfolder_share_backend_test';
		$this->subsubfolder = '/subsubfolder_share_backend_test';

		$this->filename = '/share-backend-test.txt';

		// save file with content
		$this->view->file_put_contents($this->filename, $this->data);
		$this->view->mkdir($this->folder);
		$this->view->mkdir($this->folder . $this->subfolder);
		$this->view->mkdir($this->folder . $this->subfolder . $this->subsubfolder);
		$this->view->file_put_contents($this->folder.$this->filename, $this->data);
		$this->view->file_put_contents($this->folder . $this->subfolder . $this->filename, $this->data);
		$this->view->file_put_contents($this->folder . $this->subfolder . $this->subsubfolder . $this->filename, $this->data);
	}

	protected function tearDown() {
		$this->view->unlink($this->filename);
		$this->view->deleteAll($this->folder);

		parent::tearDown();
	}

	function testGetParents() {

		$fileinfo1 = $this->view->getFileInfo($this->folder);
		$fileinfo2 = $this->view->getFileInfo($this->folder . $this->subfolder . $this->subsubfolder);
		$fileinfo3 = $this->view->getFileInfo($this->folder . $this->subfolder . $this->subsubfolder . $this->filename);

		$this->assertTrue(\OCP\Share::shareItem('folder', $fileinfo1['fileid'], \OCP\Share::SHARE_TYPE_USER,
				self::TEST_FILES_SHARING_API_USER2, 31));
		$this->assertTrue(\OCP\Share::shareItem('folder', $fileinfo2['fileid'], \OCP\Share::SHARE_TYPE_USER,
				self::TEST_FILES_SHARING_API_USER3, 31));

		$backend = new \OC_Share_Backend_Folder();

		$result = $backend->getParents($fileinfo3['fileid']);
		$this->assertSame(2, count($result));

		$count1 = 0;
		$count2 = 0;
		foreach($result as $r) {
			if ($r['path'] === 'files' . $this->folder) {
				$this->assertSame(ltrim($this->folder, '/'), $r['collection']['path']);
				$count1++;
			} elseif ($r['path'] === 'files' . $this->folder . $this->subfolder . $this->subsubfolder) {
				$this->assertSame(ltrim($this->subsubfolder, '/'), $r['collection']['path']);
				$count2++;
			} else {
				$this->assertTrue(false, 'unexpected result');
			}
		}

		$this->assertSame(1, $count1);
		$this->assertSame(1, $count2);

		$result1 = $backend->getParents($fileinfo3['fileid'], self::TEST_FILES_SHARING_API_USER3);
		$this->assertSame(1, count($result1));
		$elemet = reset($result1);
		$this->assertSame('files' . $this->folder . $this->subfolder . $this->subsubfolder ,$elemet['path']);
		$this->assertSame(ltrim($this->subsubfolder, '/') ,$elemet['collection']['path']);

	}

}
