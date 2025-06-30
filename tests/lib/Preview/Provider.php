<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Preview;

use OC\Files\Filesystem;
use OC\Files\Node\File;
use OC\Files\Storage\Storage;
use OC\Files\Storage\Temporary;
use OC\Files\View;
use OC\Preview\TXT;
use OCP\Files\IRootFolder;
use OCP\IUserManager;
use OCP\Server;

abstract class Provider extends \Test\TestCase {
	protected string $imgPath;
	protected int $width;
	protected int $height;
	/** @var \OC\Preview\Provider|mixed $provider */
	protected $provider;
	protected int $maxWidth = 1024;
	protected int $maxHeight = 1024;
	protected bool $scalingUp = false;
	protected string $userId;
	protected View $rootView;
	protected Storage $storage;

	protected function setUp(): void {
		parent::setUp();

		$userManager = Server::get(IUserManager::class);
		$userManager->clearBackends();
		$backend = new \Test\Util\User\Dummy();
		$userManager->registerBackend($backend);

		$userId = $this->getUniqueID();
		$backend->createUser($userId, $userId);
		$this->loginAsUser($userId);

		$this->storage = new Temporary([]);
		Filesystem::mount($this->storage, [], '/' . $userId . '/');

		$this->rootView = new View('');
		$this->rootView->mkdir('/' . $userId);
		$this->rootView->mkdir('/' . $userId . '/files');

		$this->userId = $userId;
	}

	protected function tearDown(): void {
		$this->logout();

		parent::tearDown();
	}

	public static function dimensionsDataProvider() {
		return [
			[-rand(5, 100), -rand(5, 100)],
			[rand(5, 100), rand(5, 100)],
			[-rand(5, 100), rand(5, 100)],
			[rand(5, 100), -rand(5, 100)],
		];
	}

	/**
	 * Launches all the tests we have
	 *
	 * @dataProvider dimensionsDataProvider
	 * @requires extension imagick
	 *
	 * @param int $widthAdjustment
	 * @param int $heightAdjustment
	 */
	public function testGetThumbnail($widthAdjustment, $heightAdjustment): void {
		$ratio = round($this->width / $this->height, 2);
		$this->maxWidth = $this->width - $widthAdjustment;
		$this->maxHeight = $this->height - $heightAdjustment;

		// Testing code
		/*print_r("w $this->width ");
		print_r("h $this->height ");
		print_r("r $ratio ");*/

		$preview = $this->getPreview($this->provider);
		// The TXT provider uses the max dimensions to create its canvas,
		// so the ratio will always be the one of the max dimension canvas
		if (!$this->provider instanceof TXT) {
			$this->doesRatioMatch($preview, $ratio);
		}
		$this->doesPreviewFit($preview);
	}

	/**
	 * Adds the test file to the filesystem
	 *
	 * @param string $fileName name of the file to create
	 * @param string $fileContent path to file to use for test
	 *
	 * @return string
	 */
	protected function prepareTestFile($fileName, $fileContent) {
		$imgData = file_get_contents($fileContent);
		$imgPath = '/' . $this->userId . '/files/' . $fileName;
		$this->rootView->file_put_contents($imgPath, $imgData);

		$scanner = $this->storage->getScanner();
		$scanner->scan('');

		return $imgPath;
	}

	/**
	 * Retrieves a max size thumbnail can be created
	 *
	 * @param \OC\Preview\Provider $provider
	 *
	 * @return bool|\OCP\IImage
	 */
	private function getPreview($provider) {
		$file = new File(Server::get(IRootFolder::class), $this->rootView, $this->imgPath);
		$preview = $provider->getThumbnail($file, $this->maxWidth, $this->maxHeight, $this->scalingUp);

		if (get_class($this) === BitmapTest::class && $preview === null) {
			$this->markTestSkipped('An error occured while operating with Imagick.');
		}

		$this->assertNotEquals(false, $preview);
		$this->assertEquals(true, $preview->valid());

		return $preview;
	}

	/**
	 * Checks if the preview ratio matches the original ratio
	 *
	 * @param \OCP\IImage $preview
	 * @param int $ratio
	 */
	private function doesRatioMatch($preview, $ratio) {
		$previewRatio = round($preview->width() / $preview->height(), 2);
		$this->assertEquals($ratio, $previewRatio);
	}

	/**
	 * Tests if a max size preview of smaller dimensions can be created
	 *
	 * @param \OCP\IImage $preview
	 */
	private function doesPreviewFit($preview) {
		$maxDimRatio = round($this->maxWidth / $this->maxHeight, 2);
		$previewRatio = round($preview->width() / $preview->height(), 2);

		// Testing code
		/*print_r("mw $this->maxWidth ");
		print_r("mh $this->maxHeight ");
		print_r("mr $maxDimRatio ");
		$pw = $preview->width();
		$ph = $preview->height();
		print_r("pw $pw ");
		print_r("ph $ph ");
		print_r("pr $previewRatio ");*/

		if ($maxDimRatio < $previewRatio) {
			$this->assertLessThanOrEqual($this->maxWidth, $preview->width());
			$this->assertLessThan($this->maxHeight, $preview->height());
		} elseif ($maxDimRatio > $previewRatio) {
			$this->assertLessThan($this->maxWidth, $preview->width());
			$this->assertLessThanOrEqual($this->maxHeight, $preview->height());
		} else { // Original had to be resized
			$this->assertLessThanOrEqual($this->maxWidth, $preview->width());
			$this->assertLessThanOrEqual($this->maxHeight, $preview->height());
		}
	}
}
