<?php
/**
 * Copyright (c) 2013 Georg Ehrke <georg@ownCloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

class Preview extends \PHPUnit_Framework_TestCase {

	public function testIsPreviewDeleted() {
		$user = $this->initFS();

		$rootView = new \OC\Files\View('');
		$rootView->mkdir('/'.$user);
		$rootView->mkdir('/'.$user.'/files');

		$samplefile = '/'.$user.'/files/test.txt';

		$rootView->file_put_contents($samplefile, 'dummy file data');
		
		$x = 50;
		$y = 50;

		$preview = new \OC\Preview($user, 'files/', 'test.txt', $x, $y);
		$preview->getPreview();

		$fileinfo = $rootView->getFileInfo($samplefile);
		$fileid = $fileinfo['fileid'];

		$thumbcachefile = '/' . $user . '/' . \OC\Preview::THUMBNAILS_FOLDER . '/' . $fileid . '/' . $x . '-' . $y . '.png';
		
		$this->assertEquals($rootView->file_exists($thumbcachefile), true);

		$preview->deletePreview();

		$this->assertEquals($rootView->file_exists($thumbcachefile), false);
	}

	public function testAreAllPreviewsDeleted() {
		$user = $this->initFS();

		$rootView = new \OC\Files\View('');
		$rootView->mkdir('/'.$user);
		$rootView->mkdir('/'.$user.'/files');

		$samplefile = '/'.$user.'/files/test.txt';

		$rootView->file_put_contents($samplefile, 'dummy file data');
		
		$x = 50;
		$y = 50;

		$preview = new \OC\Preview($user, 'files/', 'test.txt', $x, $y);
		$preview->getPreview();

		$fileinfo = $rootView->getFileInfo($samplefile);
		$fileid = $fileinfo['fileid'];
		
		$thumbcachefolder = '/' . $user . '/' . \OC\Preview::THUMBNAILS_FOLDER . '/' . $fileid . '/';
		
		$this->assertEquals($rootView->is_dir($thumbcachefolder), true);

		$preview->deleteAllPreviews();

		$this->assertEquals($rootView->is_dir($thumbcachefolder), false);
	}

	public function testIsMaxSizeWorking() {
		$user = $this->initFS();

		$maxX = 250;
		$maxY = 250;

		\OC_Config::setValue('preview_max_x', $maxX);
		\OC_Config::setValue('preview_max_y', $maxY);

		$rootView = new \OC\Files\View('');
		$rootView->mkdir('/'.$user);
		$rootView->mkdir('/'.$user.'/files');

		$samplefile = '/'.$user.'/files/test.txt';

		$rootView->file_put_contents($samplefile, 'dummy file data');

		$preview = new \OC\Preview($user, 'files/', 'test.txt', 1000, 1000);
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
		$user = $this->initFS();

		$rootView = new \OC\Files\View('');
		$rootView->mkdir('/'.$user);
		$rootView->mkdir('/'.$user.'/files');

		$x = 32;
		$y = 32;

		$sample = '/'.$user.'/files/test.'.$extension;
		$rootView->file_put_contents($sample, $data);
		$preview = new \OC\Preview($user, 'files/', 'test.'.$extension, $x, $y);
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
