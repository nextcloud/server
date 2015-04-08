<?php
/**
 * Copyright (c) 2013 Georg Ehrke <georg@ownCloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

class Preview extends TestCase {

	/**
	 * @var string
	 */
	private $user;

	/**
	 * @var \OC\Files\View
	 */
	private $rootView;

	/** @var \OC\Files\Storage\Storage */
	private $originalStorage;

	protected function setUp() {
		parent::setUp();

		// FIXME: use proper tearDown with $this->loginAsUser() and $this->logout()
		// (would currently break the tests for some reason)
		$this->originalStorage = \OC\Files\Filesystem::getStorage('/');

		// create a new user with his own filesystem view
		// this gets called by each test in this test class
		$this->user = $this->getUniqueID();
		\OC_User::setUserId($this->user);
		\OC\Files\Filesystem::init($this->user, '/' . $this->user . '/files');

		\OC\Files\Filesystem::mount('OC\Files\Storage\Temporary', array(), '/');

		$this->rootView = new \OC\Files\View('');
		$this->rootView->mkdir('/'.$this->user);
		$this->rootView->mkdir('/'.$this->user.'/files');
	}

	protected function tearDown() {
		\OC\Files\Filesystem::clearMounts();
		\OC\Files\Filesystem::mount($this->originalStorage, array(), '/');

		parent::tearDown();
	}

	public function testIsMaxSizeWorking() {
		// Max size from config
		$maxX = 1024;
		$maxY = 1024;

		\OC::$server->getConfig()->setSystemValue('preview_max_x', $maxX);
		\OC::$server->getConfig()->setSystemValue('preview_max_y', $maxY);

		// Sample is 1680x1050 JPEG
		$sampleFile = '/' . $this->user . '/files/testimage.jpg';
		$this->rootView->file_put_contents($sampleFile, file_get_contents(\OC::$SERVERROOT.'/tests/data/testimage.jpg'));
		$fileInfo = $this->rootView->getFileInfo($sampleFile);
		$fileId = $fileInfo['fileid'];

		$largeX = 1920;
		$largeY = 1080;
		$preview = new \OC\Preview($this->user, 'files/', 'testimage.jpg', $largeX, $largeY);

		$this->assertEquals($preview->isFileValid(), true);

		// There should be no cached copy
		$isCached = $preview->isCached($fileId);

		$this->assertNotEquals(\OC\Preview::THUMBNAILS_FOLDER . '/' . $fileId . '/' . $maxX . '-' . $maxY . '-max.png', $isCached);
		$this->assertNotEquals(\OC\Preview::THUMBNAILS_FOLDER . '/' . $fileId . '/' . $maxX . '-' . $maxY . '.png', $isCached);
		$this->assertNotEquals(\OC\Preview::THUMBNAILS_FOLDER . '/' . $fileId . '/' . $largeX . '-' . $largeY . '.png', $isCached);

		// The returned preview should be of max size
		$image = $preview->getPreview();

		$this->assertEquals($image->width(), $maxX);
		$this->assertEquals($image->height(), $maxY);

		// The max thumbnail should be created
		$maxThumbCacheFile = '/' . $this->user . '/' . \OC\Preview::THUMBNAILS_FOLDER . '/' . $fileId . '/' . $maxX . '-' . $maxY . '-max.png';

		$this->assertEquals($this->rootView->file_exists($maxThumbCacheFile), true);

		// A preview of the asked size should not have been created
		$thumbCacheFile = \OC\Preview::THUMBNAILS_FOLDER . '/' . $fileId . '/' . $largeX . '-' . $largeY . '.png';

		$this->assertEquals($this->rootView->file_exists($thumbCacheFile), false);

		// 2nd request should indicate that we have a cached copy of max dimension
		$isCached = $preview->isCached($fileId);
		$this->assertEquals(\OC\Preview::THUMBNAILS_FOLDER . '/' . $fileId . '/' . $maxX . '-' . $maxY . '.png', $isCached);

		// Smaller previews should be based on the cached max preview
		$smallX = 50;
		$smallY = 50;
		$preview = new \OC\Preview($this->user, 'files/', 'testimage.jpg', $smallX, $smallY);
		$isCached = $preview->isCached($fileId);

		$this->assertEquals(\OC\Preview::THUMBNAILS_FOLDER . '/' . $fileId . '/' . $maxX . '-' . $maxY . '.png', $isCached);

		// A small preview should be created
		$image = $preview->getPreview();
		$this->assertEquals($image->width(), $smallX);
		$this->assertEquals($image->height(), $smallY);

		// The cache should contain the small preview
		$thumbCacheFile = '/' . $this->user . '/' . \OC\Preview::THUMBNAILS_FOLDER . '/' . $fileId . '/' . $smallX . '-' . $smallY . '.png';

		$this->assertEquals($this->rootView->file_exists($thumbCacheFile), true);

		// 2nd request should indicate that we have a cached copy of the exact dimension
		$isCached = $preview->isCached($fileId);

		$this->assertEquals(\OC\Preview::THUMBNAILS_FOLDER . '/' . $fileId . '/' . $smallX . '-' . $smallY . '.png', $isCached);
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
}
