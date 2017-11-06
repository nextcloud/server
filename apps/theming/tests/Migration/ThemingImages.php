<?php
/**
 * @copyright Copyright (c) 2017 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Haertl <jus@bitgrid.net>
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

namespace OCA\Theming\Tests\Migration;

use OCP\Files\File;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Migration\IOutput;
use Test\TestCase;
use OCA\Theming\Migration\ThemingImages;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;

class ThemingImagesTest extends TestCase {
	/** @var ThemingImages */
	private $repairStep;
	/** @var IAppData */
	private $appData;
	/** @var IRootFolder */
	private $rootFolder;
	/** @var ISimpleFolder */
	private $imageFolder;
	/** @var IOutput */
	private $output;

	public function setUp() {
		parent::setUp();
		$this->appData = $this->createMock(IAppData::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->repairStep = new ThemingImages($this->appData, $this->rootFolder);
		$this->imageFolder = $this->createMock(ISimpleFolder::class);
		$this->output = $this->createMock(IOutput::class);
	}

	public function testGetName() {
		$this->assertEquals(
			'Move theming files to AppData storage',
			$this->repairStep->getName()
		);
	}

	public function testRunNoImages() {
		$this->appData->expects($this->once())
			->method('newFolder')
			->willReturn($this->imageFolder);
		$this->rootFolder->expects($this->any())
			->method('get')
			->willThrowException(new NotFoundException());
		$this->imageFolder->expects($this->never())
			->method('newFile');
		$this->output->expects($this->exactly(2))
			->method('info');
		$this->repairStep->run($this->output);
	}

	public function testRunLogo() {
		$oldFile = $this->createMock(File::class);
		$newFile = $this->createMock(ISimpleFile::class);

		$this->appData->expects($this->once())
			->method('newFolder')
			->willReturn($this->imageFolder);
		$this->rootFolder->expects($this->at(1))
			->method('get')
			->with('themedbackgroundlogo')
			->willThrowException(new NotFoundException());
		$this->rootFolder->expects($this->at(0))
			->method('get')
			->with('themedinstancelogo')
			->willReturn($oldFile);
		$this->imageFolder->expects($this->once())
			->method('newFile')
			->with('logo')
			->willReturn($newFile);
		$oldFile->expects($this->once())
			->method('getContent')
			->willReturn('data');
		$newFile->expects($this->once())
			->method('putContent')
			->with('data');
		$oldFile->expects($this->once())
			->method('delete');

		$this->repairStep->run($this->output);
	}

	public function testRunBackground() {
		$oldFile = $this->createMock(File::class);
		$newFile = $this->createMock(ISimpleFile::class);
		
		$this->appData->expects($this->once())
			->method('newFolder')
			->willReturn($this->imageFolder);
		$this->rootFolder->expects($this->at(1))
			->method('get')
			->with('themedbackgroundlogo')
			->willReturn($oldFile);
		$this->rootFolder->expects($this->at(0))
			->method('get')
			->with('themedinstancelogo')
			->willThrowException(new NotFoundException());
		$this->imageFolder->expects($this->once())
			->method('newFile')
			->with('background')
			->willReturn($newFile);
		$oldFile->expects($this->once())
			->method('getContent')
			->willReturn('data');
		$newFile->expects($this->once())
			->method('putContent')
			->with('data');
		$oldFile->expects($this->once())
			->method('delete');

		$this->repairStep->run($this->output);
	}
}
