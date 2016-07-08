<?php
/**
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Olivier Paroz <owncloud@interfasys.ch>
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

namespace Test;

use OC\Files\FileInfo;
use OC\Files\Storage\Temporary;
use OC\Files\View;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

/**
 * Class PreviewTest
 *
 * @group DB
 *
 * @package Test
 */
class PreviewTest extends TestCase {
	use UserTrait;
	use MountProviderTrait;

	const TEST_PREVIEW_USER1 = "test-preview-user1";

	/** @var \OC\Files\View */
	private $rootView;
	/**
	 * Note that using 756 with an image with a ratio of 1.6 brings interesting rounding issues
	 *
	 * @var int maximum width allowed for a preview
	 * */
	private $configMaxWidth = 756;
	/** @var int maximum height allowed for a preview */
	private $configMaxHeight = 756;
	private $keepAspect;
	private $scalingUp;

	private $samples = [];
	private $sampleFileId;
	private $sampleFilename;
	private $sampleWidth;
	private $sampleHeight;
	private $maxScaleFactor;
	/** @var int width of the max preview */
	private $maxPreviewWidth;
	/** @var int height of the max preview */
	private $maxPreviewHeight;
	/** @var int height of the max preview, which is the same as the one of the original image */
	private $maxPreviewRatio;
	private $cachedBigger = [];

	/**
	 * Make sure your configuration file doesn't contain any additional providers
	 */
	protected function setUp() {
		parent::setUp();

		$this->createUser(self::TEST_PREVIEW_USER1, self::TEST_PREVIEW_USER1);
		$this->loginAsUser(self::TEST_PREVIEW_USER1);

		$storage = new \OC\Files\Storage\Temporary([]);
		\OC\Files\Filesystem::mount($storage, [], '/' . self::TEST_PREVIEW_USER1 . '/');

		$this->rootView = new \OC\Files\View('');
		$this->rootView->mkdir('/' . self::TEST_PREVIEW_USER1);
		$this->rootView->mkdir('/' . self::TEST_PREVIEW_USER1 . '/files');

		// We simulate the max dimension set in the config
		\OC::$server->getConfig()
			->setSystemValue('preview_max_x', $this->configMaxWidth);
		\OC::$server->getConfig()
			->setSystemValue('preview_max_y', $this->configMaxHeight);
		// Used to test upscaling
		$this->maxScaleFactor = 2;
		\OC::$server->getConfig()
			->setSystemValue('preview_max_scale_factor', $this->maxScaleFactor);

		// We need to enable the providers we're going to use in the tests
		$providers = [
			'OC\\Preview\\JPEG',
			'OC\\Preview\\PNG',
			'OC\\Preview\\GIF',
			'OC\\Preview\\TXT',
			'OC\\Preview\\Postscript'
		];
		\OC::$server->getConfig()
			->setSystemValue('enabledPreviewProviders', $providers);

		// Sample is 1680x1050 JPEG
		$this->prepareSample('testimage.jpg', 1680, 1050);
		// Sample is 2400x1707 EPS
		$this->prepareSample('testimage.eps', 2400, 1707);
		// Sample is 1200x450 PNG
		$this->prepareSample('testimage-wide.png', 1200, 450);
		// Sample is 64x64 GIF
		$this->prepareSample('testimage.gif', 64, 64);
	}

	protected function tearDown() {
		$this->logout();

		parent::tearDown();
	}

	/**
	 * Tests if a preview can be deleted
	 */
	public function testIsPreviewDeleted() {

		$sampleFile = '/' . self::TEST_PREVIEW_USER1 . '/files/test.txt';

		$this->rootView->file_put_contents($sampleFile, 'dummy file data');

		$x = 50;
		$y = 50;

		$preview = new \OC\Preview(self::TEST_PREVIEW_USER1, 'files/', 'test.txt', $x, $y);
		$preview->getPreview();

		$fileInfo = $this->rootView->getFileInfo($sampleFile);
		/** @var int $fileId */
		$fileId = $fileInfo['fileid'];
		$thumbCacheFile = $this->buildCachePath($fileId, $x, $y, true);

		$this->assertSame(
			true, $this->rootView->file_exists($thumbCacheFile), "$thumbCacheFile \n"
		);

		$preview->deletePreview();

		$this->assertSame(false, $this->rootView->file_exists($thumbCacheFile));
	}

	/**
	 * Tests if all previews can be deleted
	 *
	 * We test this first to make sure we'll be able to cleanup after each preview generating test
	 */
	public function testAreAllPreviewsDeleted() {

		$sampleFile = '/' . self::TEST_PREVIEW_USER1 . '/files/test.txt';

		$this->rootView->file_put_contents($sampleFile, 'dummy file data');

		$x = 50;
		$y = 50;

		$preview = new \OC\Preview(self::TEST_PREVIEW_USER1, 'files/', 'test.txt', $x, $y);
		$preview->getPreview();

		$fileInfo = $this->rootView->getFileInfo($sampleFile);
		/** @var int $fileId */
		$fileId = $fileInfo['fileid'];

		$thumbCacheFolder = '/' . self::TEST_PREVIEW_USER1 . '/' . \OC\Preview::THUMBNAILS_FOLDER .
			'/' . $fileId . '/';

		$this->assertSame(true, $this->rootView->is_dir($thumbCacheFolder), "$thumbCacheFolder \n");

		$preview->deleteAllPreviews();

		$this->assertSame(false, $this->rootView->is_dir($thumbCacheFolder));
	}

	public function txtBlacklist() {
		$txt = 'random text file';

		return [
			['txt', $txt, false],
		];
	}

	/**
	 * @dataProvider txtBlacklist
	 *
	 * @param $extension
	 * @param $data
	 * @param $expectedResult
	 */
	public function testIsTransparent($extension, $data, $expectedResult) {

		$x = 32;
		$y = 32;

		$sample = '/' . self::TEST_PREVIEW_USER1 . '/files/test.' . $extension;
		$this->rootView->file_put_contents($sample, $data);
		$preview = new \OC\Preview(
			self::TEST_PREVIEW_USER1, 'files/', 'test.' . $extension, $x,
			$y
		);
		$image = $preview->getPreview();
		$resource = $image->resource();

		//http://stackoverflow.com/questions/5702953/imagecolorat-and-transparency
		$colorIndex = imagecolorat($resource, 1, 1);
		$colorInfo = imagecolorsforindex($resource, $colorIndex);
		$this->assertSame(
			$expectedResult,
			$colorInfo['alpha'] === 127,
			'Failed asserting that only previews for text files are transparent.'
		);
	}

	/**
	 * Tests if unsupported previews return an empty object
	 */
	public function testUnsupportedPreviewsReturnEmptyObject() {
		$width = 400;
		$height = 200;

		// Previews for odt files are not enabled
		$imgData = file_get_contents(\OC::$SERVERROOT . '/tests/data/testimage.odt');
		$imgPath = '/' . self::TEST_PREVIEW_USER1 . '/files/testimage.odt';
		$this->rootView->file_put_contents($imgPath, $imgData);

		$preview =
			new \OC\Preview(self::TEST_PREVIEW_USER1, 'files/', 'testimage.odt', $width, $height);
		$preview->getPreview();
		$image = $preview->getPreview();

		$this->assertSame(false, $image->valid());
	}

	/**
	 * We generate the data to use as it makes it easier to adjust in case we need to test
	 * something different
	 *
	 * @return array
	 */
	public static function dimensionsDataProvider() {
		$data = [];
		$samples = [
			[200, 800],
			[200, 800],
			[50, 400],
			[4, 60],
		];
		$keepAspect = false;
		$scalingUp = false;

		for ($a = 0; $a < sizeof($samples); $a++) {
			for ($b = 0; $b < 2; $b++) {
				for ($c = 0; $c < 2; $c++) {
					for ($d = 0; $d < 4; $d++) {
						$coordinates = [
							[
								-rand($samples[$a][0], $samples[$a][1]),
								-rand($samples[$a][0], $samples[$a][1])
							],
							[
								rand($samples[$a][0], $samples[$a][1]),
								rand($samples[$a][0], $samples[$a][1])
							],
							[
								-rand($samples[$a][0], $samples[$a][1]),
								rand($samples[$a][0], $samples[$a][1])
							],
							[
								rand($samples[$a][0], $samples[$a][1]),
								-rand($samples[$a][0], $samples[$a][1])
							]
						];
						$row = [$a];
						$row[] = $coordinates[$d][0];
						$row[] = $coordinates[$d][1];
						$row[] = $keepAspect;
						$row[] = $scalingUp;
						$data[] = $row;
					}
					$scalingUp = !$scalingUp;
				}
				$keepAspect = !$keepAspect;
			}
		}

		return $data;
	}

	/**
	 * Tests if a preview of max dimensions gets created
	 *
	 * @requires extension imagick
	 * @dataProvider dimensionsDataProvider
	 *
	 * @param int $sampleId
	 * @param int $widthAdjustment
	 * @param int $heightAdjustment
	 * @param bool $keepAspect
	 * @param bool $scalingUp
	 */
	public function testCreateMaxAndNormalPreviewsAtFirstRequest(
		$sampleId, $widthAdjustment, $heightAdjustment, $keepAspect = false, $scalingUp = false
	) {
		//$this->markTestSkipped('Not testing this at this time');

		// Get the right sample for the experiment
		$this->getSample($sampleId);
		$sampleWidth = $this->sampleWidth;
		$sampleHeight = $this->sampleHeight;
		$sampleFileId = $this->sampleFileId;

		// Adjust the requested size so that we trigger various test cases
		$previewWidth = $sampleWidth + $widthAdjustment;
		$previewHeight = $sampleHeight + $heightAdjustment;
		$this->keepAspect = $keepAspect;
		$this->scalingUp = $scalingUp;

		// Generates the max preview
		$preview = $this->createPreview($previewWidth, $previewHeight);

		// There should be no cached thumbnails
		$thumbnailFolder = '/' . self::TEST_PREVIEW_USER1 . '/' . \OC\Preview::THUMBNAILS_FOLDER .
			'/' . $sampleFileId;
		$this->assertSame(false, $this->rootView->is_dir($thumbnailFolder));

		$image = $preview->getPreview();
		$this->assertNotSame(false, $image);

		$maxThumbCacheFile = $this->buildCachePath(
			$sampleFileId, $this->maxPreviewWidth, $this->maxPreviewHeight, true, '-max'
		);

		$this->assertSame(
			true, $this->rootView->file_exists($maxThumbCacheFile), "$maxThumbCacheFile \n"
		);

		// We check the dimensions of the file we've just stored
		$maxPreview = imagecreatefromstring($this->rootView->file_get_contents($maxThumbCacheFile));

		$this->assertEquals($this->maxPreviewWidth, imagesx($maxPreview));
		$this->assertEquals($this->maxPreviewHeight, imagesy($maxPreview));

		// A thumbnail of the asked dimensions should also have been created (within the constraints of the max preview)
		list($limitedPreviewWidth, $limitedPreviewHeight) =
			$this->simulatePreviewDimensions($previewWidth, $previewHeight);

		$actualWidth = $image->width();
		$actualHeight = $image->height();

		$this->assertEquals(
			(int)$limitedPreviewWidth, $image->width(), "$actualWidth x $actualHeight \n"
		);
		$this->assertEquals((int)$limitedPreviewHeight, $image->height());

		// And it should be cached
		$this->checkCache($sampleFileId, $limitedPreviewWidth, $limitedPreviewHeight);

		$preview->deleteAllPreviews();
	}

	/**
	 * Tests if the second preview will be based off the cached max preview
	 *
	 * @requires extension imagick
	 * @dataProvider dimensionsDataProvider
	 *
	 * @param int $sampleId
	 * @param int $widthAdjustment
	 * @param int $heightAdjustment
	 * @param bool $keepAspect
	 * @param bool $scalingUp
	 */
	public function testSecondPreviewsGetCachedMax(
		$sampleId, $widthAdjustment, $heightAdjustment, $keepAspect = false, $scalingUp = false
	) {
		//$this->markTestSkipped('Not testing this at this time');

		$this->getSample($sampleId);
		$sampleWidth = $this->sampleWidth;
		$sampleHeight = $this->sampleHeight;
		$sampleFileId = $this->sampleFileId;

		//Creates the Max preview which will be used in the rest of the test
		$this->createMaxPreview();

		// Adjust the requested size so that we trigger various test cases
		$previewWidth = $sampleWidth + $widthAdjustment;
		$previewHeight = $sampleHeight + $heightAdjustment;
		$this->keepAspect = $keepAspect;
		$this->scalingUp = $scalingUp;

		$preview = $this->createPreview($previewWidth, $previewHeight);

		// A cache query should return the thumbnail of max dimension
		$isCached = $preview->isCached($sampleFileId);
		$cachedMaxPreview = $this->buildCachePath(
			$sampleFileId, $this->maxPreviewWidth, $this->maxPreviewHeight, false, '-max'
		);
		$this->assertSame($cachedMaxPreview, $isCached);
	}

	/**
	 * Make sure that the max preview can never be deleted
	 *
	 * For this test to work, the preview we generate first has to be the size of max preview
	 */
	public function testMaxPreviewCannotBeDeleted() {
		//$this->markTestSkipped('Not testing this at this time');

		$this->keepAspect = true;
		$this->getSample(0);
		$fileId = $this->sampleFileId;

		//Creates the Max preview which we will try to delete
		$preview = $this->createMaxPreview();

		// We try to deleted the preview
		$preview->deletePreview();
		$this->assertNotSame(false, $preview->isCached($fileId));

		$preview->deleteAllPreviews();
	}

	public static function aspectDataProvider() {
		$data = [];
		$samples = 4;
		$keepAspect = false;
		$scalingUp = false;
		for ($a = 0; $a < $samples; $a++) {
			for ($b = 0; $b < 2; $b++) {
				for ($c = 0; $c < 2; $c++) {
					$row = [$a];
					$row[] = $keepAspect;
					$row[] = $scalingUp;
					$data[] = $row;
					$scalingUp = !$scalingUp;
				}
				$keepAspect = !$keepAspect;
			}
		}

		return $data;
	}

	/**
	 * We ask for a preview larger than what is set in the configuration,
	 * so we should be getting either the max preview or a preview the size
	 * of the dimensions set in the config
	 *
	 * @requires extension imagick
	 * @dataProvider aspectDataProvider
	 *
	 * @param int $sampleId
	 * @param bool $keepAspect
	 * @param bool $scalingUp
	 */
	public function testDoNotCreatePreviewsLargerThanConfigMax(
		$sampleId, $keepAspect = false, $scalingUp = false
	) {
		//$this->markTestSkipped('Not testing this at this time');

		$this->getSample($sampleId);

		//Creates the Max preview which will be used in the rest of the test
		$this->createMaxPreview();

		// Now we will create the real preview
		$previewWidth = 4000;
		$previewHeight = 4000;
		$this->keepAspect = $keepAspect;
		$this->scalingUp = $scalingUp;

		// Tries to create the very large preview
		$preview = $this->createPreview($previewWidth, $previewHeight);

		$image = $preview->getPreview();
		$this->assertNotSame(false, $image);

		list($expectedWidth, $expectedHeight) =
			$this->simulatePreviewDimensions($previewWidth, $previewHeight);
		$this->assertEquals($expectedWidth, $image->width());
		$this->assertEquals($expectedHeight, $image->height());

		// A preview of the asked size should not have been created since it's larger that our max dimensions
		$postfix = $this->getThumbnailPostfix($previewWidth, $previewHeight);
		$thumbCacheFile = $this->buildCachePath(
			$this->sampleFileId, $previewWidth, $previewHeight, false, $postfix
		);
		$this->assertSame(
			false, $this->rootView->file_exists($thumbCacheFile), "$thumbCacheFile \n"
		);

		$preview->deleteAllPreviews();
	}

	/**
	 * Makes sure we're getting the proper cached thumbnail
	 *
	 * When we start by generating a preview which keeps the aspect ratio
	 * 200-125-with-aspect
	 * 300-300    ✓
	 *
	 * When we start by generating a preview of exact dimensions
	 * 200-200    ✓
	 * 300-188-with-aspect
	 *
	 * @requires extension imagick
	 * @dataProvider aspectDataProvider
	 *
	 * @param int $sampleId
	 * @param bool $keepAspect
	 * @param bool $scalingUp
	 */
	public function testIsBiggerWithAspectRatioCached(
		$sampleId, $keepAspect = false, $scalingUp = false
	) {
		//$this->markTestSkipped('Not testing this at this time');

		$previewWidth = 400;
		$previewHeight = 400;
		$this->getSample($sampleId);
		$fileId = $this->sampleFileId;
		$this->keepAspect = $keepAspect;
		$this->scalingUp = $scalingUp;

		// Caching the max preview in our preview array for the test
		$this->cachedBigger[] = $this->buildCachePath(
			$fileId, $this->maxPreviewWidth, $this->maxPreviewHeight, false, '-max'
		);

		$this->getSmallerThanMaxPreview($fileId, $previewWidth, $previewHeight);
		// We switch the aspect ratio, to generate a thumbnail we should not be picked up
		$this->keepAspect = !$keepAspect;
		$this->getSmallerThanMaxPreview($fileId, $previewWidth + 100, $previewHeight + 100);

		// Small thumbnails are always cropped
		$this->keepAspect = false;
		// Smaller previews should be based on the previous, larger preview, with the correct aspect ratio
		$this->createThumbnailFromBiggerCachedPreview($fileId, 32, 32);

		// 2nd cache query should indicate that we have a cached copy of the exact dimension
		$this->getCachedSmallThumbnail($fileId, 32, 32);

		// We create a preview in order to be able to delete the cache
		$preview = $this->createPreview(rand(), rand());
		$preview->deleteAllPreviews();
		$this->cachedBigger = [];
	}

	/**
	 * Initialises the preview
	 *
	 * @param int $width
	 * @param int $height
	 *
	 * @return \OC\Preview
	 */
	private function createPreview($width, $height) {
		$preview = new \OC\Preview(
			self::TEST_PREVIEW_USER1, 'files/', $this->sampleFilename, $width,
			$height
		);

		$this->assertSame(true, $preview->isFileValid());

		$preview->setKeepAspect($this->keepAspect);
		$preview->setScalingup($this->scalingUp);

		return $preview;
	}

	/**
	 * Creates the Max preview which will be used in the rest of the test
	 *
	 * @return \OC\Preview
	 */
	private function createMaxPreview() {
		$this->keepAspect = true;
		$preview = $this->createPreview($this->maxPreviewWidth, $this->maxPreviewHeight);
		$preview->getPreview();

		return $preview;
	}

	/**
	 * Makes sure the preview which was just created has been saved to disk
	 *
	 * @param int $fileId
	 * @param int $previewWidth
	 * @param int $previewHeight
	 */
	private function checkCache($fileId, $previewWidth, $previewHeight) {
		$postfix = $this->getThumbnailPostfix($previewWidth, $previewHeight);

		$thumbCacheFile = $this->buildCachePath(
			$fileId, $previewWidth, $previewHeight, true, $postfix
		);

		$this->assertSame(
			true, $this->rootView->file_exists($thumbCacheFile), "$thumbCacheFile \n"
		);
	}

	/**
	 * Computes special filename postfixes
	 *
	 * @param int $width
	 * @param int $height
	 *
	 * @return string
	 */
	private function getThumbnailPostfix($width, $height) {
		// Need to take care of special postfix added to the dimensions
		$postfix = '';
		$isMaxPreview = ($width === $this->maxPreviewWidth
			&& $height === $this->maxPreviewHeight) ? true : false;
		if ($isMaxPreview) {
			$postfix = '-max';
		}
		if ($this->keepAspect && !$isMaxPreview) {
			$postfix = '-with-aspect';
		}

		return $postfix;
	}

	private function getSmallerThanMaxPreview($fileId, $previewWidth, $previewHeight) {
		$preview = $this->createPreview($previewWidth, $previewHeight);

		$image = $preview->getPreview();
		$this->assertNotSame(false, $image);

		// A thumbnail of the asked dimensions should also have been created (within the constraints of the max preview)
		list($limitedPreviewWidth, $limitedPreviewHeight) =
			$this->simulatePreviewDimensions($previewWidth, $previewHeight);

		$this->assertEquals($limitedPreviewWidth, $image->width());
		$this->assertEquals($limitedPreviewHeight, $image->height());

		// And it should be cached
		$this->checkCache($fileId, $limitedPreviewWidth, $limitedPreviewHeight);

		$this->cachedBigger[] = $preview->isCached($fileId);
	}

	private function createThumbnailFromBiggerCachedPreview($fileId, $width, $height) {
		$preview = $this->createPreview($width, $height);

		// A cache query should return a thumbnail of slightly larger dimensions
		// and with the proper aspect ratio
		$isCached = $preview->isCached($fileId);
		$expectedCachedBigger = $this->getExpectedCachedBigger();

		$this->assertSame($expectedCachedBigger, $isCached);

		$image = $preview->getPreview();
		$this->assertNotSame(false, $image);
	}

	/**
	 * Picks the bigger cached preview with the correct aspect ratio or the max preview if it's
	 * smaller than that
	 *
	 * For non-upscaled images, we pick the only picture without aspect ratio
	 *
	 * @return string
	 */
	private function getExpectedCachedBigger() {
		$foundPreview = null;
		$foundWidth = null;
		$foundHeight = null;
		$maxPreview = null;
		$maxWidth = null;
		$maxHeight = null;

		foreach ($this->cachedBigger as $cached) {
			$size = explode('-', basename($cached));
			$width = (int)$size[0];
			$height = (int)$size[1];

			if (strpos($cached, 'max')) {
				$maxWidth = $width;
				$maxHeight = $height;
				$maxPreview = $cached;
				continue;
			}

			// We pick the larger preview with no aspect ratio
			if (!strpos($cached, 'aspect') && !strpos($cached, 'max')) {
				$foundPreview = $cached;
				$foundWidth = $width;
				$foundHeight = $height;
			}
		}
		if ($foundWidth > $maxWidth && $foundHeight > $maxHeight) {
			$foundPreview = $maxPreview;
		}

		return $foundPreview;
	}

	/**
	 * A small thumbnail of exact dimensions should be in the cache
	 *
	 * @param int $fileId
	 * @param int $width
	 * @param int $height
	 */
	private function getCachedSmallThumbnail($fileId, $width, $height) {
		$preview = $this->createPreview($width, $height);

		$isCached = $preview->isCached($fileId);
		$thumbCacheFile = $this->buildCachePath($fileId, $width, $height);

		$this->assertSame($thumbCacheFile, $isCached, "$thumbCacheFile \n");
	}

	/**
	 * Builds the complete path to a cached thumbnail starting from the user folder
	 *
	 * @param int $fileId
	 * @param int $width
	 * @param int $height
	 * @param bool $user
	 * @param string $postfix
	 *
	 * @return string
	 */
	private function buildCachePath($fileId, $width, $height, $user = false, $postfix = '') {
		$userPath = '';
		if ($user) {
			$userPath = '/' . self::TEST_PREVIEW_USER1 . '/';
		}

		return $userPath . \OC\Preview::THUMBNAILS_FOLDER . '/' . $fileId
		. '/' . $width . '-' . $height . $postfix . '.png';
	}

	/**
	 * Stores the sample in the filesystem and stores it in the $samples array
	 *
	 * @param string $fileName
	 * @param int $sampleWidth
	 * @param int $sampleHeight
	 */
	private function prepareSample($fileName, $sampleWidth, $sampleHeight) {
		$imgData = file_get_contents(\OC::$SERVERROOT . '/tests/data/' . $fileName);
		$imgPath = '/' . self::TEST_PREVIEW_USER1 . '/files/' . $fileName;
		$this->rootView->file_put_contents($imgPath, $imgData);
		$fileInfo = $this->rootView->getFileInfo($imgPath);

		list($maxPreviewWidth, $maxPreviewHeight) =
			$this->setMaxPreview($sampleWidth, $sampleHeight);

		$this->samples[] =
			[
				'sampleFileId' => $fileInfo['fileid'],
				'sampleFileName' => $fileName,
				'sampleWidth' => $sampleWidth,
				'sampleHeight' => $sampleHeight,
				'maxPreviewWidth' => $maxPreviewWidth,
				'maxPreviewHeight' => $maxPreviewHeight
			];
	}

	/**
	 * Sets the variables used to define the boundaries which need to be respected when using a
	 * specific sample
	 *
	 * @param $sampleId
	 */
	private function getSample($sampleId) {
		// Corrects a rounding difference when using the EPS (Imagick converted) sample
		$filename = $this->samples[$sampleId]['sampleFileName'];
		$splitFileName = pathinfo($filename);
		$extension = $splitFileName['extension'];
		$correction = ($extension === 'eps' && PHP_MAJOR_VERSION < 7) ? 1 : 0;
		$maxPreviewHeight = $this->samples[$sampleId]['maxPreviewHeight'];
		$maxPreviewHeight = $maxPreviewHeight - $correction;

		$this->sampleFileId = $this->samples[$sampleId]['sampleFileId'];
		$this->sampleFilename = $this->samples[$sampleId]['sampleFileName'];
		$this->sampleWidth = $this->samples[$sampleId]['sampleWidth'];
		$this->sampleHeight = $this->samples[$sampleId]['sampleHeight'];
		$this->maxPreviewWidth = $this->samples[$sampleId]['maxPreviewWidth'];
		$this->maxPreviewHeight = $maxPreviewHeight;
		$ratio = $this->maxPreviewWidth / $this->maxPreviewHeight;
		$this->maxPreviewRatio = $ratio;
	}

	/**
	 * Defines the size of the max preview
	 *
	 * @fixme the Imagick previews don't have the exact same size on disk as they're calculated here
	 *
	 * @param int $sampleWidth
	 * @param int $sampleHeight
	 *
	 * @return array
	 */
	private function setMaxPreview($sampleWidth, $sampleHeight) {
		// Max previews are never scaled up
		$this->scalingUp = false;
		// Max previews always keep the aspect ratio
		$this->keepAspect = true;
		// We set this variable in order to be able to calculate the max preview with the proper aspect ratio
		$this->maxPreviewRatio = $sampleWidth / $sampleHeight;
		$maxPreviewWidth = min($sampleWidth, $this->configMaxWidth);
		$maxPreviewHeight = min($sampleHeight, $this->configMaxHeight);
		list($maxPreviewWidth, $maxPreviewHeight) =
			$this->applyAspectRatio($maxPreviewWidth, $maxPreviewHeight);

		return [$maxPreviewWidth, $maxPreviewHeight];
	}

	/**
	 * Calculates the expected dimensions of the preview to be able to assess if we've got the
	 * right result
	 *
	 * @param int $askedWidth
	 * @param int $askedHeight
	 *
	 * @return array
	 */
	private function simulatePreviewDimensions($askedWidth, $askedHeight) {
		$askedWidth = min($askedWidth, $this->configMaxWidth);
		$askedHeight = min($askedHeight, $this->configMaxHeight);

		if ($this->keepAspect) {
			// Defines the box in which the preview has to fit
			$scaleFactor = $this->scalingUp ? $this->maxScaleFactor : 1;
			$newPreviewWidth = min($askedWidth, $this->maxPreviewWidth * $scaleFactor);
			$newPreviewHeight = min($askedHeight, $this->maxPreviewHeight * $scaleFactor);
			list($newPreviewWidth, $newPreviewHeight) =
				$this->applyAspectRatio($newPreviewWidth, $newPreviewHeight);
		} else {
			list($newPreviewWidth, $newPreviewHeight) =
				$this->fixSize($askedWidth, $askedHeight);
		}

		return [(int)$newPreviewWidth, (int)$newPreviewHeight];
	}

	/**
	 * Resizes the boundaries to match the aspect ratio
	 *
	 * @param int $askedWidth
	 * @param int $askedHeight
	 *
	 * @return \int[]
	 */
	private function applyAspectRatio($askedWidth, $askedHeight) {
		$originalRatio = $this->maxPreviewRatio;
		if ($askedWidth / $originalRatio < $askedHeight) {
			$askedHeight = round($askedWidth / $originalRatio);
		} else {
			$askedWidth = round($askedHeight * $originalRatio);
		}

		return [(int)$askedWidth, (int)$askedHeight];
	}

	/**
	 * Clips or stretches the dimensions so that they fit in the boundaries
	 *
	 * @param int $askedWidth
	 * @param int $askedHeight
	 *
	 * @return array
	 */
	private function fixSize($askedWidth, $askedHeight) {
		if ($this->scalingUp) {
			$askedWidth = min($this->configMaxWidth, $askedWidth);
			$askedHeight = min($this->configMaxHeight, $askedHeight);
		}

		return [(int)$askedWidth, (int)$askedHeight];
	}

	public function testKeepAspectRatio() {
		$originalWidth = 1680;
		$originalHeight = 1050;
		$originalAspectRation = $originalWidth / $originalHeight;

		$preview = new \OC\Preview(
			self::TEST_PREVIEW_USER1, 'files/', 'testimage.jpg',
			150,
			150
		);
		$preview->setKeepAspect(true);
		$image = $preview->getPreview();

		$aspectRatio = $image->width() / $image->height();
		$this->assertEquals(round($originalAspectRation, 2), round($aspectRatio, 2));

		$this->assertLessThanOrEqual(150, $image->width());
		$this->assertLessThanOrEqual(150, $image->height());
	}

	public function testKeepAspectRatioCover() {
		$originalWidth = 1680;
		$originalHeight = 1050;
		$originalAspectRation = $originalWidth / $originalHeight;

		$preview = new \OC\Preview(
			self::TEST_PREVIEW_USER1, 'files/', 'testimage.jpg',
			150,
			150
		);
		$preview->setKeepAspect(true);
		$preview->setMode(\OC\Preview::MODE_COVER);
		$image = $preview->getPreview();

		$aspectRatio = $image->width() / $image->height();
		$this->assertEquals(round($originalAspectRation, 2), round($aspectRatio, 2));

		$this->assertGreaterThanOrEqual(150, $image->width());
		$this->assertGreaterThanOrEqual(150, $image->height());
	}

	public function testSetFileWithInfo() {
		$info = new FileInfo('/foo', null, '/foo', ['mimetype' => 'foo/bar'], null);
		$preview = new \OC\Preview();
		$preview->setFile('/foo', $info);
		$this->assertEquals($info, $this->invokePrivate($preview, 'getFileInfo'));
	}

	public function testIsCached() {
		$sourceFile = __DIR__ . '/../data/testimage.png';
		$userId = $this->getUniqueID();
		$this->createUser($userId, 'pass');

		$storage = new Temporary();
		$storage->mkdir('files');
		$this->registerMount($userId, $storage, '/' . $userId);

		\OC_Util::tearDownFS();
		\OC_Util::setupFS($userId);
		$preview = new \OC\Preview($userId, 'files');
		$view = new View('/' . $userId . '/files');
		$view->file_put_contents('test.png', file_get_contents($sourceFile));
		$info = $view->getFileInfo('test.png');
		$preview->setFile('test.png', $info);

		$preview->setMaxX(64);
		$preview->setMaxY(64);

		$this->assertFalse($preview->isCached($info->getId()));

		$preview->getPreview();

		$this->assertEquals('thumbnails/' . $info->getId() . '/64-64.png', $preview->isCached($info->getId()));
	}
}
