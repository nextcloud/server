<?php
/**
 * @author Piotr Mrowczynski piotr@owncloud.com
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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
namespace OCA\DAV\Tests\unit\DAV;

use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCA\DAV\Connector\Sabre\File as DavFile;
use OCA\DAV\DAV\ViewOnlyPlugin;
use OCA\Files_Sharing\SharedStorage;
use OCA\Files_Versions\Sabre\VersionFile;
use OCA\Files_Versions\Versions\IVersion;
use OCP\Files\File;
use OCP\Files\Storage\IStorage;
use OCP\Share\IAttributes;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Test\TestCase;

class ViewOnlyPluginTest extends TestCase {

	private ViewOnlyPlugin $plugin;
	/** @var Tree | \PHPUnit\Framework\MockObject\MockObject */
	private $tree;
	/** @var RequestInterface | \PHPUnit\Framework\MockObject\MockObject */
	private $request;

	public function setUp(): void {
		$this->plugin = new ViewOnlyPlugin(
			$this->createMock(LoggerInterface::class)
		);
		$this->request = $this->createMock(RequestInterface::class);
		$this->tree = $this->createMock(Tree::class);

		$server = $this->createMock(Server::class);
		$server->tree = $this->tree;

		$this->plugin->initialize($server);
	}

	public function testCanGetNonDav(): void {
		$this->request->expects($this->once())->method('getPath')->willReturn('files/test/target');
		$this->tree->method('getNodeForPath')->willReturn(null);

		$this->assertTrue($this->plugin->checkViewOnly($this->request));
	}

	public function testCanGetNonShared(): void {
		$this->request->expects($this->once())->method('getPath')->willReturn('files/test/target');
		$davNode = $this->createMock(DavFile::class);
		$this->tree->method('getNodeForPath')->willReturn($davNode);

		$file = $this->createMock(File::class);
		$davNode->method('getNode')->willReturn($file);

		$storage = $this->createMock(IStorage::class);
		$file->method('getStorage')->willReturn($storage);
		$storage->method('instanceOfStorage')->with(SharedStorage::class)->willReturn(false);

		$this->assertTrue($this->plugin->checkViewOnly($this->request));
	}

	public function providesDataForCanGet(): array {
		return [
			// has attribute permissions-download enabled - can get file
			[false, true, true],
			// has no attribute permissions-download - can get file
			[false, null, true],
			// has attribute permissions-download disabled- cannot get the file
			[false, false, false],
			// has attribute permissions-download enabled - can get file version
			[true, true, true],
			// has no attribute permissions-download - can get file version
			[true, null, true],
			// has attribute permissions-download disabled- cannot get the file version
			[true, false, false],
		];
	}

	/**
	 * @dataProvider providesDataForCanGet
	 */
	public function testCanGet(bool $isVersion, ?bool $attrEnabled, bool $expectCanDownloadFile): void {
		$nodeInfo = $this->createMock(File::class);
		if ($isVersion) {
			$davPath = 'versions/alice/versions/117/123456';
			$version = $this->createMock(IVersion::class);
			$version->expects($this->once())
				->method('getSourceFile')
				->willReturn($nodeInfo);
			$davNode = $this->createMock(VersionFile::class);
			$davNode->expects($this->once())
				->method('getVersion')
				->willReturn($version);
		} else {
			$davPath = 'files/path/to/file.odt';
			$davNode = $this->createMock(DavFile::class);
			$davNode->method('getNode')->willReturn($nodeInfo);
		}

		$this->request->expects($this->once())->method('getPath')->willReturn($davPath);

		$this->tree->expects($this->once())
			 ->method('getNodeForPath')
			 ->with($davPath)
			 ->willReturn($davNode);

		$storage = $this->createMock(SharedStorage::class);
		$share = $this->createMock(IShare::class);
		$nodeInfo->expects($this->once())
			->method('getStorage')
			->willReturn($storage);
		$storage->method('instanceOfStorage')->with(SharedStorage::class)->willReturn(true);
		$storage->method('getShare')->willReturn($share);

		$extAttr = $this->createMock(IAttributes::class);
		$share->method('getAttributes')->willReturn($extAttr);
		$extAttr->expects($this->once())
		  ->method('getAttribute')
		  ->with('permissions', 'download')
		  ->willReturn($attrEnabled);

		if (!$expectCanDownloadFile) {
			$this->expectException(Forbidden::class);
		}
		$this->plugin->checkViewOnly($this->request);
	}
}
