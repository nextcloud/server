<?php
/**
 * Copyright (c) 2013 Georg Ehrke <georg@ownCloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

class Preview extends \PHPUnit_Framework_TestCase {

	/**
	 * @var string
	 */
	private $user;

	/**
	 * @var \OC\Files\View
	 */
	private $rootView;

	public function setUp() {
		$this->user = $this->initFS();

		$this->rootView = new \OC\Files\View('');
		$this->rootView->mkdir('/'.$this->user);
		$this->rootView->mkdir('/'.$this->user.'/files');
	}

	public function testIsPreviewDeleted() {

		$sampleFile = '/'.$this->user.'/files/test.txt';

		$this->rootView->file_put_contents($sampleFile, 'dummy file data');
		
		$x = 50;
		$y = 50;

		$preview = new \OC\Preview($this->user, 'files/', 'test.txt', $x, $y);
		$preview->getPreview();

		$fileInfo = $this->rootView->getFileInfo($sampleFile);
		$fileId = $fileInfo['fileid'];

		$thumbCacheFile = '/' . $this->user . '/' . \OC\Preview::THUMBNAILS_FOLDER . '/' . $fileId . '/' . $x . '-' . $y . '.png';
		
		$this->assertEquals($this->rootView->file_exists($thumbCacheFile), true);

		$preview->deletePreview();

		$this->assertEquals($this->rootView->file_exists($thumbCacheFile), false);
	}

	public function testAreAllPreviewsDeleted() {

		$sampleFile = '/'.$this->user.'/files/test.txt';

		$this->rootView->file_put_contents($sampleFile, 'dummy file data');
		
		$x = 50;
		$y = 50;

		$preview = new \OC\Preview($this->user, 'files/', 'test.txt', $x, $y);
		$preview->getPreview();

		$fileInfo = $this->rootView->getFileInfo($sampleFile);
		$fileId = $fileInfo['fileid'];
		
		$thumbCacheFolder = '/' . $this->user . '/' . \OC\Preview::THUMBNAILS_FOLDER . '/' . $fileId . '/';
		
		$this->assertEquals($this->rootView->is_dir($thumbCacheFolder), true);

		$preview->deleteAllPreviews();

		$this->assertEquals($this->rootView->is_dir($thumbCacheFolder), false);
	}

	public function testIsMaxSizeWorking() {

		$maxX = 250;
		$maxY = 250;

		\OC_Config::setValue('preview_max_x', $maxX);
		\OC_Config::setValue('preview_max_y', $maxY);

		$sampleFile = '/'.$this->user.'/files/test.txt';

		$this->rootView->file_put_contents($sampleFile, 'dummy file data');

		$preview = new \OC\Preview($this->user, 'files/', 'test.txt', 1000, 1000);
		$image = $preview->getPreview();

		$this->assertEquals($image->width(), $maxX);
		$this->assertEquals($image->height(), $maxY);
	}

	public function txtBlacklist() {
		$txt = 'random text file';

		return array(
			array('txt', $txt, false),
		);
	}

	/**
	 * @dataProvider txtBlacklist
	 */
	public function testIsTransparent($extension, $data, $expectedResult) {

		$x = 32;
		$y = 32;

		$sample = '/'.$this->user.'/files/test.'.$extension;
		$this->rootView->file_put_contents($sample, $data);
		$preview = new \OC\Preview($this->user, 'files/', 'test.'.$extension, $x, $y);
		$image = $preview->getPreview();
		$resource = $image->resource();

		//http://stackoverflow.com/questions/5702953/imagecolorat-and-transparency
		$colorIndex = imagecolorat($resource, 1, 1);
		$colorInfo = imagecolorsforindex($resource, $colorIndex);
		$this->assertEquals(
			$expectedResult,
			$colorInfo['alpha'] === 127,
			'Failed asserting that only previews for text files are transparent.'
		);
	}

	public function testCreationFromCached() {

		$sampleFile = '/'.$this->user.'/files/test.txt';

		$this->rootView->file_put_contents($sampleFile, 'dummy file data');

		// create base preview
		$x = 150;
		$y = 150;

		$preview = new \OC\Preview($this->user, 'files/', 'test.txt', $x, $y);
		$preview->getPreview();

		$fileInfo = $this->rootView->getFileInfo($sampleFile);
		$fileId = $fileInfo['fileid'];

		$thumbCacheFile = '/' . $this->user . '/' . \OC\Preview::THUMBNAILS_FOLDER . '/' . $fileId . '/' . $x . '-' . $y . '.png';

		$this->assertEquals($this->rootView->file_exists($thumbCacheFile), true);


		// create smaller previews
		$preview = new \OC\Preview($this->user, 'files/', 'test.txt', 50, 50);
		$isCached = $preview->isCached($fileId);

		$this->assertEquals($this->user . '/' . \OC\Preview::THUMBNAILS_FOLDER . '/' . $fileId . '/150-150.png', $isCached);
	}

	/*
	public function testScalingUp() {

		$sampleFile = '/'.$this->user.'/files/test.txt';

		$this->rootView->file_put_contents($sampleFile, 'dummy file data');

		// create base preview
		$x = 150;
		$y = 150;

		$preview = new \OC\Preview($this->user, 'files/', 'test.txt', $x, $y);
		$preview->getPreview();

		$fileInfo = $this->rootView->getFileInfo($sampleFile);
		$fileId = $fileInfo['fileid'];

		$thumbCacheFile = '/' . $this->user . '/' . \OC\Preview::THUMBNAILS_FOLDER . '/' . $fileId . '/' . $x . '-' . $y . '.png';

		$this->assertEquals($this->rootView->file_exists($thumbCacheFile), true);


		// create bigger previews - with scale up
		$preview = new \OC\Preview($this->user, 'files/', 'test.txt', 250, 250);
		$isCached = $preview->isCached($fileId);

		$this->assertEquals($this->user . '/' . \OC\Preview::THUMBNAILS_FOLDER . '/' . $fileId . '/150-150.png', $isCached);
	}
	*/

	private function initFS() {
		// create a new user with his own filesystem view
		// this gets called by each test in this test class
		$user=uniqid();
		\OC_User::setUserId($user);
		\OC\Files\Filesystem::init($user, '/'.$user.'/files');

		\OC\Files\Filesystem::mount('OC\Files\Storage\Temporary', array(), '/');
		
		return $user;
	}
}
